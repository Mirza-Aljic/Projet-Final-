#include <QtWidgets>
#include <QObject>
#include <QtSql/QSqlDatabase>
#include <QtSql/QSqlQuery>
#include <QtSql/QSqlError>
#include <QDebug>
#include <QApplication>
#include <cmath>
#include "DatabaseInsertTable.h"
#include "DatabaseSelector.h"
#include "DatabaseSelectorForLoad.h"
#include "PanelBDWindow.h"
#include "ProgressWindow.h"

#include <QtGlobal>

using namespace std;

double sigmoid(double sum){
    double X = 1.0 / (1.0 + std::exp(-sum));
    return X;
}

double sigmoid_derivative(double x) {
    double sig = sigmoid(x);
    return sig * (1.0 - sig);
}

int main(int argc, char *argv[])
{




    QApplication app(argc, argv);

    QWidget window;

    int INPUT_SIZE = 0;
    int HIDDEN_SIZE = 0;
    double LEARNING_RATE = 0.0;
    int EPOCHS = 0;
    int OUTPUT_SIZE = 0;

    ProgressWindow progressWindow;

    QVBoxLayout *mainLayout = new QVBoxLayout;

    QVBoxLayout *buttonLayout = new QVBoxLayout;
    QPushButton *button1 = new QPushButton("train");
    QPushButton *button2 = new QPushButton("predict");
    QPushButton *button3 = new QPushButton("Load");
    QPushButton *button4 = new QPushButton("save");
    button2->setEnabled(false);
    button4->setEnabled(false);


    buttonLayout->addWidget(button1);
    buttonLayout->addWidget(button2);
    buttonLayout->addWidget(button3);
    buttonLayout->addWidget(button4);


    QHBoxLayout *sliderLayout = new QHBoxLayout;
    QSlider *slider1 = new QSlider(Qt::Horizontal);
    QSlider *slider2 = new QSlider(Qt::Horizontal);
    QSlider *slider3 = new QSlider(Qt::Horizontal);

    QLabel *label1 = new QLabel("Params : 1");
    QLabel *label2 = new QLabel("Nodes : 1");
    QLabel *label3 = new QLabel("Rate : 0.00");

    QLabel *title1 = new QLabel("Input Layer");
    QLabel *title2 = new QLabel("Hidden Layer");
    QLabel *title3 = new QLabel("Output Layer");

    slider1->setMinimum(1);
    slider1->setMaximum(255);
    slider1->setValue(7);
    INPUT_SIZE = slider1->value();
    label1->setText(QString("Params : %1").arg(7));

    slider2->setMinimum(1);
    slider2->setMaximum(255);
    slider2->setValue(20);
    label2->setText(QString("Nodes : %1").arg(20));
    HIDDEN_SIZE = slider2->value();

    slider3->setMinimum(0);
    slider3->setMaximum(99);
    slider3->setValue(1);
    label3->setText(QString("Rate : %1").arg(0.01));
    LEARNING_RATE = (double)slider3->value()/100;
    OUTPUT_SIZE = slider3->value();

    sliderLayout->addWidget(title1);
    sliderLayout->addWidget(slider1);
    sliderLayout->addWidget(label1);
    sliderLayout->addWidget(title2);
    sliderLayout->addWidget(slider2);
    sliderLayout->addWidget(label2);
    sliderLayout->addWidget(title3);
    sliderLayout->addWidget(slider3);
    sliderLayout->addWidget(label3);

    QDial *dial = new QDial;
    QLabel *labelDial = new QLabel("Epochs : 0");

    dial->setMinimum(0);
    dial->setMaximum(500000);
    dial->setSingleStep(10000);
    dial->setValue(400000);
    EPOCHS = dial->value();
    QObject::connect(dial, &QDial::valueChanged,[&](int value){
        EPOCHS = value;
        labelDial->setText(QString("Epochs : %1").arg(value));
    });

    QGroupBox *groupBox1 = new QGroupBox("Training");
    QGroupBox *groupBox2 = new QGroupBox("Predict");

    QTableView *tableView1 = new QTableView;
    QTableView *tableView2 = new QTableView;

    QStandardItemModel model1(30,10);
    QStandardItemModel model2(30,10);

    tableView1 -> setModel(&model1);
    tableView2 -> setModel(&model2);

    QVBoxLayout *groupBoxLayout1 = new QVBoxLayout;
    QVBoxLayout *groupBoxLayout2 = new QVBoxLayout;

    groupBoxLayout1->addWidget(tableView1);
    groupBoxLayout2->addWidget(tableView2);

    groupBox1->setLayout(groupBoxLayout1);
    groupBox2->setLayout(groupBoxLayout2);

    QHBoxLayout *buttonsAndSlidersLayout = new QHBoxLayout;
    buttonsAndSlidersLayout->addLayout(buttonLayout);
    buttonsAndSlidersLayout->addLayout(sliderLayout);

    QVBoxLayout *dialLayout = new QVBoxLayout;
    dialLayout->addWidget(dial);
    dialLayout->addWidget(labelDial);

    QHBoxLayout *tableViewLayout = new QHBoxLayout;
    tableViewLayout->addWidget(groupBox1);
    tableViewLayout->addWidget(groupBox2);


    mainLayout->addLayout(buttonsAndSlidersLayout);
    mainLayout->addLayout(dialLayout);
    mainLayout->addLayout(tableViewLayout);

    window.setLayout(mainLayout);
    window.show();


    QObject::connect(button1, &QPushButton::clicked, [button2, button4, groupBox1, HIDDEN_SIZE, INPUT_SIZE, OUTPUT_SIZE, LEARNING_RATE, EPOCHS, &progressWindow](){
        button2->setEnabled(true);
        button4->setEnabled(true);
        PanelBDWindow panel("infos de la bdd", nullptr);
        panel.exec();
        QString passWord = panel.getPassword();
        QString URL = panel.getURL();
        QString username = panel.getUsername();
        QString DB = panel.getDB();
        int port = panel.getPort();
        DatabaseSelector *data = new DatabaseSelector(URL, DB, username, passWord, port, nullptr);


        QString selectedTable;

        QObject::connect(data, &DatabaseSelector::tableSelected, [&selectedTable](const QString &tableName) {
            selectedTable = tableName; // Mettre à jour la variable avec la table sélectionnée
            qDebug() << "Table sélectionnée : " << selectedTable;
        });

        double** GLOBALweights_ih = (double**)malloc(HIDDEN_SIZE * sizeof(double *));
        for (int i = 0; i < HIDDEN_SIZE; ++i){
            GLOBALweights_ih[i] = (double *)malloc(INPUT_SIZE * sizeof(double));
        }
        double** GLOBALweights_ho = (double**)malloc(OUTPUT_SIZE * sizeof(double *));
        for (int i = 0; i < OUTPUT_SIZE; ++i){
            GLOBALweights_ho[i] = (double *)malloc(HIDDEN_SIZE * sizeof(double));
        }

        QSqlQuery query;
        QSqlRecord record;

        QString queryStr = "SELECT * FROM " + selectedTable ;

        query.exec(queryStr);
        if (query.exec(queryStr)) {
            qDebug() << "Erreur lors de la requête : " << query.lastError().text();
        }

        int numberOfRows = query.size();
        int numberOfColumns = query.record().count();
        double** matriceX = (double **)malloc(numberOfRows * sizeof(double));
        for(int i = 0; i <numberOfRows; i++){
            matriceX[i] = (double *)malloc((numberOfColumns+1) * sizeof(double));
        }
        double* matriceY = (double *)malloc(numberOfRows * sizeof(double));
        double** matriceTableViewTrain = (double **)malloc(numberOfRows * sizeof(double *));
        for(int i = 0; i < numberOfRows; i++) {
            matriceTableViewTrain[i] = (double *)malloc(numberOfColumns * sizeof(double));
        }

        qDebug() << "récupération des données sur la table : "<<data<<"requête : "<<queryStr;
        int i = 0;
        if(query.exec(queryStr)){
            record = query.record();
            while(query.next()){
                qDebug()<<"while";
                for (int j = 0; j < numberOfColumns - 1; j++){
                    qDebug()<<"matriceX";
                    matriceX[i][j] = query.value(j).toDouble();
                    qDebug()<<"matriceX"<<matriceX[i][j];
                }

                matriceY[i] = query.value(numberOfColumns-1).toDouble();
                qDebug()<<"matriceY"<<matriceY[i];
                for(int j = 0; j < numberOfColumns; j++){
                    matriceTableViewTrain[i][j] = query.value(j).toDouble();
                    qDebug()<<"matriceTableViewTrain"<<matriceTableViewTrain[i][j];
                }
                i++;
            }
        }
        else{
            qDebug()<<"Erreur lors de la requête : " << query.lastError().text();
        }

        double** weights_ih = (double **)malloc(HIDDEN_SIZE * sizeof(double *));
        for(int i = 0; i < HIDDEN_SIZE; i++) {
            weights_ih[i] = (double *)malloc(INPUT_SIZE * sizeof(double));
            for(int j = 0; j < INPUT_SIZE; j++) {
                weights_ih[i][j] = ((double)rand() / RAND_MAX) * 2 - 1; // Initialisation aléatoire entre -1 et 1
            }
        }

        double** weights_ho = (double **)malloc(OUTPUT_SIZE * sizeof(double *));
        for(int i = 0; i < OUTPUT_SIZE; i++) {
            weights_ho[i] = (double *)malloc(HIDDEN_SIZE * sizeof(double));
            for(int j = 0; j < HIDDEN_SIZE; j++) {
                weights_ho[i][j] = ((double)rand() / RAND_MAX) * 2 - 1; // Initialisation aléatoire entre -1 et 1
            }
        }
        for(int epoch = 0; epoch < EPOCHS; epoch++) {
            for(int i = 0; i < numberOfRows; i++) {
                // Propagation avant
                double *hidden = (double *)malloc(HIDDEN_SIZE * sizeof(double));
                for(int j = 0; j < HIDDEN_SIZE; j++) {
                    double sum = 0;
                    for(int k = 0; k < INPUT_SIZE; k++) {
                        sum += matriceX[i][k] * weights_ih[j][k];
                    }
                    hidden[j] = sigmoid(sum);
                }

                double output = 0;
                for(int j = 0; j < HIDDEN_SIZE; j++) {
                    output += hidden[j] * weights_ho[0][j];
                }
                output = sigmoid(output);

                // Calcul de l'erreur
                double error = matriceY[i] - output;

                // Rétropropagation
                double d_output = error * sigmoid_derivative(output);
                for(int j = 0; j < HIDDEN_SIZE; j++) {
                    weights_ho[0][j] += LEARNING_RATE * d_output * hidden[j];
                }

                for(int j = 0; j < HIDDEN_SIZE; j++) {
                    double d_hidden = d_output * weights_ho[0][j] * sigmoid_derivative(hidden[j]);
                    for(int k = 0; k < INPUT_SIZE; k++) {
                        weights_ih[j][k] += LEARNING_RATE * d_hidden * matriceX[i][k];
                    }
                }

                free(hidden);
            }
            progressWindow.updateProgress(epoch);
            QApplication::processEvents(); // Permet à l'interface utilisateur de se mettre à jour
        }
        // RECOPIE DES TABLEAUX DE POIDS SOIT MON RESEAU DE NEURONES EQUILIBRE
        GLOBALweights_ho = weights_ho;

        for(int i = 0; i < OUTPUT_SIZE; i++) {
            for(int j = 0; j < HIDDEN_SIZE; j++) {
                GLOBALweights_ho[i][j] = weights_ho[i][j];
                qDebug() << "ho [" << i << "][" << j << "] : " << GLOBALweights_ho[i][j];
            }
        }

        GLOBALweights_ih = weights_ih;

        for(int i = 0; i < HIDDEN_SIZE; i++) {
            for(int j = 0; j < INPUT_SIZE; j++) {
                GLOBALweights_ih[i][j] = weights_ih[i][j];
                qDebug() << "ih [" << i << "][" << j << "] : " << GLOBALweights_ih[i][j];
            }
        }

        qDebug()<< "IH : " << GLOBALweights_ih;
        qDebug()<< "HO : " << GLOBALweights_ho;




    });

    QObject::connect(button3,&QPushButton::clicked,[button2](){
        button2->setEnabled(true);
        PanelBDWindow Panel("infos de la bdd", nullptr);
        Panel.exec();
        QString passWord = Panel.getPassword();
        QString URL = Panel.getURL();
        QString username = Panel.getUsername();
        QString DB = Panel.getDB();
        int port = Panel.getPort();

    } );

    QObject::connect(button4, &QPushButton::clicked, [button4]{

    });

    return app.exec();



}
