#include <QApplication>
#include <QWidget>
#include <QPainter>
#include <QKeyEvent>
#include <QEvent>
#include <MazeWidget.h>
#include <QVBoxLayout>
#include <QPushButton>
#include <api_manager.h>


int main(int argc, char *argv[]) {
    QApplication app(argc, argv);

    QWidget mainWindow;
    mainWindow.setWindowTitle("Labyrinthe IA");
    ApiManager manager;
    QVBoxLayout* layout = new QVBoxLayout(&mainWindow);

    MazeWidget* maze = new MazeWidget();
    QPushButton* restartButton = new QPushButton("Recommencer");
    QPushButton* aleatoireButton = new QPushButton("mode aleatoire");
    QPushButton* manuelButton = new QPushButton("mode manuel");
    QPushButton* IAButton = new QPushButton("mode IA");

    manager.getHO();
    manager.getIH();

    layout->addWidget(restartButton);
    layout->addWidget(aleatoireButton);
    layout->addWidget(manuelButton);
    layout->addWidget(IAButton);
    layout->addWidget(maze);

    // Connexion du bouton à une fonction (ex : remettre à la position de départ)
    QObject::connect(restartButton, &QPushButton::clicked, [=]() {
        maze->stopFunction();
        maze->setXY(1, 1); // Retourne au point de départ
        maze->setMoveCount(0);
    });

    QObject::connect(aleatoireButton, &QPushButton::clicked, [=](){
        maze->activerTimer();
        maze->activerManuel();
        maze->autoMove();

    });

    QObject::connect(manuelButton, &QPushButton::clicked, [=]() {
        maze->activerTimer();

        maze->activerManuel();
        qDebug() << "Mode manuel activé";

    });

    QObject::connect(IAButton, &QPushButton::clicked, [=](){
        maze->activerManuel();
        maze->IAMove();
    });


    mainWindow.setLayout(layout);
    mainWindow.show();

    return app.exec();
}
