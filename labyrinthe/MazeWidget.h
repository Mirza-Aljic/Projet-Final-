#ifndef MAZEWIDGET_H
#define MAZEWIDGET_H

#include <api_manager.h>

#include <QPaintEvent>
#include <QMouseEvent>
#include <QKeyEvent>
#include <QWidget>
#include <QApplication>
#include <random>
#include <QElapsedTimer>
#include <algorithm> // Pour std::max_element


class MazeWidget : public QWidget {
private :
    int X;
    int Y;
    std::mt19937 rng;
    int bestPathLenght;
    int moveCount = 0;
    QStringList moveHistory;
    bool manuelMode = false;
    bool Execution = false;  // Flag pour arrêter l'exécution
    QElapsedTimer timer;
    ApiManager *api;
    QTimer* Timer = nullptr;



public:
    MazeWidget(QWidget *parent = nullptr);
    void setXY(int x, int y);
    void update();
    void setFocus();
    void autoMove();
    double sigmoid(double x);
    void IAMove();
    void activerManuel();
    void activerTimer();
    void stopFunction();
    void setMoveCount(int MoveCount);
    ~MazeWidget();

protected:
    void paintEvent(QPaintEvent *event) override;
    void mousePressEvent(QMouseEvent * event) override;
    void keyPressEvent(QKeyEvent *event ) override;
};

#endif // MAZEWIDGET_H
