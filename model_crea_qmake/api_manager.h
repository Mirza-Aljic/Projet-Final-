#ifndef ApiManager_HPP
#define ApiManager_HPP

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

    QVector<QVector<double>> fetchTables(); // méthode GET
    // D'autres fonctions plus tard, comme sendData(), fetchUser(), etc.
    void sendDataIH(int input_index, int hidden_index, float weight);
    void sendDataHO(int output_index, int hidden_index, float weight);
    void deleteAllEntriesHO();
    void deleteAllEntriesIH();
signals:
    void requestFinished(const QString &response);
    void requestError(const QString &error);

};

#endif
