#include "MazeWidget.h"
#include <QPainter>
#include <qtimer.h>
#include <QDebug>
#include <cmath>


// Labyrinthe : 1 = mur, 0 = chemin, 2 = sortie, 3 = départ
static const std::vector<std::vector<int>> maze = {
    {1, 1, 1, 1, 1, 1, 1, 1, 1, 1},
    {1, 3, 0, 0, 0, 0, 0, 0, 0, 1},
    {1, 0, 1, 1, 0, 1, 1, 1, 0, 1},
    {1, 0, 1, 0, 0, 0, 0, 1, 0, 1},
    {1, 0, 0, 0, 2, 1, 0, 1, 0, 1},
    {1, 0, 1, 0, 1, 1, 0, 1, 0, 1},
    {1, 0, 1, 0, 0, 0, 0, 1, 0, 1},
    {1, 0, 1, 1, 1, 1, 1, 1, 0, 1},
    {1, 0, 0, 0, 0, 0, 0, 0, 0, 1},
    {1, 1, 1, 1, 1, 1, 1, 1, 1, 1}
};

MazeWidget::MazeWidget(QWidget *parent)
    : QWidget(parent), X(1), Y(1)
{
    setFixedSize(400, 400);  // Taille de la fenêtre
    setFocusPolicy(Qt::StrongFocus);  // Permet la gestion du clavier
    api = new ApiManager(this);
    connect(api, &ApiManager::requestFinished, this, [](const QString &response) {
        qDebug() << "Réponse reçue dans SimulationController:" << response;

    });

    connect(api, &ApiManager::requestError, this, [](const QString &error) {
        qDebug() << "Erreur reçue dans SimulationController:" << error;
    });
}

MazeWidget::~MazeWidget() {}

void MazeWidget::setXY(int x, int y) {
    X = x;
    Y = y;
    update();
}

void MazeWidget::update() {
    QWidget::update();
}

void MazeWidget::setFocus() {
    QWidget::setFocus();
}

void MazeWidget::setMoveCount(int MoveCount){
    moveCount = MoveCount;
}

void MazeWidget::activerTimer(){
    timer.start();
}

void MazeWidget::paintEvent(QPaintEvent *) {

    QPainter painter(this);
    int rows = maze.size();
    int cols = maze[0].size();
    int cellSize = width() / cols;

    for (int y = 0; y < rows; ++y) {
        for (int x = 0; x < cols; ++x) {
            switch (maze[y][x]) {
            case 0: painter.setBrush(Qt::white); break;
            case 1: painter.setBrush(Qt::black); break;
            case 2: painter.setBrush(Qt::green); break;
            case 3: painter.setBrush(Qt::blue); break;
            }
            painter.drawRect(x * cellSize, y * cellSize, cellSize, cellSize);
        }
    }

    // Dessine le joueur
    painter.setBrush(Qt::red);
    painter.drawEllipse(X * cellSize + 5, Y * cellSize + 5, cellSize - 10, cellSize - 10);
}

void MazeWidget::mousePressEvent(QMouseEvent *event) {
    Q_UNUSED(event);
}

void MazeWidget::keyPressEvent(QKeyEvent *event) {

    moveCount++;
    if (!manuelMode){
        return;
    }

    int newX = X;
    int newY = Y;

    switch (event->key()) {
    case Qt::Key_Escape: QApplication::quit();
    case Qt::Key_Left:   newX--; break;
    case Qt::Key_Right:  newX++; break;
    case Qt::Key_Up:     newY--; break;
    case Qt::Key_Down:   newY++; break;
    case Qt::Key_Space: IAMove();
        return;
    case Qt::Key_D: autoMove(); break;
        return;
    default: return;
    }

    // Empêche d'aller dans un mur
    if (maze[newY][newX] != 1) {
        X = newX;
        Y = newY;
        update();
    }

    // Message si on atteint la fin
    if (maze[Y][X] == 2) {
        qDebug() << "Bravo, vous avez gagné !";
        qDebug() << "coups " << moveCount;
        int temps = static_cast<int>(timer.elapsed());
        qDebug() << "temps passé " << temps ;
        int sec = round(temps/1000);
        //QApplication::quit(); // <- Quitte proprement l'application
        api->sendSimulationData(moveCount, sec, "manuel");


    }

}

void MazeWidget::autoMove() {
    Execution = true;
    if (Timer != nullptr && Timer->isActive()) {
        // Le timer est déjà en cours
        qDebug() << "autoMove déjà en cours.";
        return;
    }

    Timer = new QTimer(this);

    std::random_device rd;
    std::mt19937 gen(rd());
    std::uniform_int_distribution<> dirDist(0, 3); // 0=up, 1=right, 2=down, 3=left
    moveCount = 0;
    //Chronometre::demarrer();
    connect(Timer, &QTimer::timeout, [=]() mutable {
        if (maze[Y][X] == 2) { // Check if reached exit
            qDebug() << "Reached the exit!";
            Timer->stop();
            Timer->deleteLater();
            Timer = nullptr;
            int temps = static_cast<int>(timer.elapsed());
            int sec = round(temps/1000);
            api->sendSimulationData(moveCount, sec, "Aleatoire");

            return;
        }

        if (manuelMode==false){
            qDebug() << "manuelMode désactivé, arrêt du timer.";
            Timer->stop();
            Timer->deleteLater();
            Timer = nullptr;
            return;
        }

        int direction = dirDist(gen);
        int newX = X;
        int newY = Y;

        switch (direction) {
        case 0: newY--; break; // Haut
        case 1: newX++; break; // Droite
        case 2: newY++; break; // Bas
        case 3: newX--; break; // Gauche
        }

        moveCount++;

        QString dirString;
        if (direction == 0) dirString = "Haut";
        else if (direction == 1) dirString = "Droite";
        else if (direction == 2) dirString = "Bas";
        else if (direction == 3) dirString = "Gauche";

        // Vérifie si le mouvement est possible (pas hors des limites et pas dans un mur)
        if (newX >= 0 && newX < (int)maze[0].size() &&
            newY >= 0 && newY < (int)maze.size() &&
            maze[newY][newX] != 1) {
            X = newX;
            Y = newY;
            qDebug() << "Coup #" << moveCount << ": " << dirString;
        }
        else {
            qDebug() << "Coup #" << moveCount << ": " << dirString << " bloqué (mur ou bord)";
        }
        moveHistory.append(dirString);
        update();
    });

    Timer->start(300); // Déplacement toutes les 300ms
}


double MazeWidget::sigmoid(double x) {
    return 1.0 / (1.0 + exp(-x));
}

void MazeWidget::IAMove() {
    QVector<QVector<double>> IHweight = api->getIH();  // 20 x 7
    QVector<QVector<double>> HOweight = api->getHO();  // 20 x 1


    if (!Timer) {
        Timer = new QTimer(this);

        connect(Timer, &QTimer::timeout, [=]() mutable {
            // --- Partie que tu gères toi-même ---
            QVector<double> input(7); // à remplir avec tes données d'entrée
            // Delta X et Y (à calculer avec un historique, ici un exemple simple)
            int lastX = X, lastY = Y;
            if (moveHistory.size() >= 2) {
                QString prevMove = moveHistory[moveHistory.size() - 2];
                if (prevMove == "Haut")    lastY++;
                if (prevMove == "Bas")     lastY--;
                if (prevMove == "Gauche")  lastX++;
                if (prevMove == "Droite")  lastX--;
            }
            input[0] = X - lastX;
            input[1] = (-1*(Y - lastY));
            // réussite
            if (X == 2 && Y == 2) {
                input[2] = 1;
            }

            else{
                input[2] = 0;
            }

            // Case au-dessus
            input[3] = (Y > 0) ? maze[Y - 1][X] : 1; // Bord = mur (1)

            // Case en dessous

            input[4] = (Y < maze.size() - 1) ? maze[Y + 1][X] : 1;

            // Case à droite
            input[5] = (X < maze[0].size() - 1) ? maze[Y][X + 1] : 1;


            // Case à gauche
            input[6] = (X > 0) ? maze[Y][X - 1] : 1;



            auto sigmoid = [](double x) {
                return 1.0 / (1.0 + std::exp(-x));
            };

            // Couche cachée
            QVector<double> hidden(20, 0.0);
            for (int h = 0; h < 20; ++h) {
                double sum = 0.0;
                for (int i = 0; i < 7; ++i) {
                    sum += input[i] * IHweight[h][i];
                }
                hidden[h] = sigmoid(sum);
            }

            // Sortie
            QVector<double> outputs(5, 0.0);
            for (int o = 0; o < 5; ++o) {
                double sum = 0.0;
                for (int h = 0; h < 20; ++h) {
                    sum += hidden[h] * HOweight[o][h];  // HO devient 5 x 20
                }
                outputs[o] = sigmoid(sum);
            }


            qDebug()<<outputs;


            int direction = 0;
            double maxVal = outputs[0];
            for (int i = 1; i < outputs.size(); ++i) {
                if (outputs[i] > maxVal) {
                    maxVal = outputs[i];
                    direction = i;
                }
            }
            // --- Tu gères ce que tu fais avec "direction" ---


            int newX = X;
            int newY = Y;


        switch (direction) {
            case 0: newX++; break; // Droite
            case 1: newY++; break; // Bas
            case 2: newX--; break; // Gauche
            case 3: newY--; break; // Haut

            }
            X = newX;
            Y = newY;
        });
        Timer->start(300); // Déplacement toutes les 300ms
    }
}


void MazeWidget::activerManuel(){
    manuelMode = true;

}

void MazeWidget::stopFunction(){

    manuelMode = false;
}
