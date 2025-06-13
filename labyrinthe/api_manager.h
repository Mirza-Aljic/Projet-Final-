#ifndef API_MANAGER_H
#define API_MANAGER_H
#include <curl/curl.h>
#include <QJsonDocument>
#include <QJsonObject>
#include <QDebug>
#include <QObject>
#include <QTime>
#include <QString>
#include <QJsonArray>


class ApiManager : public QObject
{
    Q_OBJECT
public:
    explicit ApiManager(QObject *parent = nullptr);

    void sendSimulationData(int nombre_coups, int &duree_simulation, const QString &modele_simulation);
    QVector<QVector<double>> getIH();
    QVector<QVector<double>> getHO();

signals:
    void requestFinished(const QString &response);
    void requestError(const QString &error);
};

#endif // API_MANAGER_H
