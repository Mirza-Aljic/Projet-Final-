#include <api_manager.h>
#include <iostream>

size_t WriteCallback_fetch(void* contents, size_t size, size_t nmemb, void* userp)
{
    size_t totalSize = size * nmemb;
    QString* buffer = static_cast<QString*>(userp);
    buffer->append(QString::fromUtf8(static_cast<char*>(contents), static_cast<int>(totalSize)));
    return totalSize;
}

size_t WriteCallback(void* contents, size_t size, size_t nmemb, void* userp)
{
    size_t totalSize = size * nmemb;
    std::string* buffer = static_cast<std::string*>(userp);
    buffer->append(static_cast<char*>(contents), totalSize);
    return totalSize;
}
ApiManager::ApiManager(QObject *parent) : QObject(parent) {}

QVector<QVector<double>> ApiManager::fetchTables()
{
    QVector<QVector<double>> dataMatrix;
    qDebug() << "fetchTables called";

    CURL* curl = curl_easy_init();
    if (curl)
    {
        QString readBuffer;
        //struct curl_slist *headers = NULL;

        //headers = curl_slist_append(headers, "Authorization: Bearer VOTRE_TOKEN");

        curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_vtre/get_data.php");
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback_fetch);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);
        //curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
        CURLcode res = curl_easy_perform(curl);
        if (res != CURLE_OK)
        {
            qWarning() << "curl_easy_perform() failed:" << curl_easy_strerror(res);
            curl_easy_cleanup(curl);
            return dataMatrix;
        }

        curl_easy_cleanup(curl);

        QJsonDocument jsonDoc = QJsonDocument::fromJson(readBuffer.toUtf8());
        if (!jsonDoc.isObject())
        {
            qWarning() << "Invalid JSON format!";
            return dataMatrix;
        }

        QJsonObject rootObj = jsonDoc.object();
        QJsonArray valuesArray = rootObj["data"].toArray();

        for (const QJsonValue& entryVal : valuesArray)
        {
            if (!entryVal.isObject())
                continue;

            QJsonObject entryObj = entryVal.toObject();
            QVector<double> row;
            QStringList orderedKeys = {"x", "y", "esc", "up", "down", "right_r", "left_l"};
            for (const QString& key : orderedKeys) {
                row.append(entryObj[key].toDouble());
            }

            // Ensuite ajouter la tag à la fin
            row.append(entryObj["tag"].toDouble());

            dataMatrix.append(row);
        }
    }
    else
    {
        qWarning() << "Failed to initialize CURL!";
    }
    for (int i = 0; i < dataMatrix.size(); ++i) {
        QStringList rowStrings;
        for (double val : dataMatrix[i]) {
            rowStrings << QString::number(val);
        }
        qDebug() << "Row" << i << ":" << rowStrings.join(", ");
    }
    return dataMatrix;
}





void ApiManager::sendDataIH(int input_index, int hidden_index, float weight)
{
    CURL *curl;
    CURLcode res;
    std::string readBuffer;

    curl = curl_easy_init();
    if (curl)
    {


        // Création de l'objet JSON avec Qt
        QJsonObject json;
        json["input_index"] = input_index;
        json["hidden_index"] = hidden_index;
        json["weight"] = weight;

        QJsonDocument doc(json);
        QByteArray jsonData = doc.toJson();



        struct curl_slist *headers = nullptr;
        headers = curl_slist_append(headers, "Content-Type: application/json");
        //headers = curl_slist_append(headers, "Authorization: Bearer VOTRE_TOKEN");

        curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_msi/post_data.php");
        curl_easy_setopt(curl, CURLOPT_POSTFIELDS, jsonData.constData());
        curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback); // pour capturer la réponse
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);       // buffer pour la réponse
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
        QString response = QString::fromStdString(readBuffer);



        res = curl_easy_perform(curl);



        if (res != CURLE_OK)
        {
            QString error = QString::fromUtf8(curl_easy_strerror(res));
            qDebug() << "Erreur CURL:" << error;
            emit requestError(error);
        }
        else
        {
            QString response = QString::fromStdString(readBuffer);
            qDebug() << "Réponse CURL:" << response;
            emit requestFinished(response);
        }

        curl_slist_free_all(headers);
        curl_easy_cleanup(curl);
    }
    else
    {
        emit requestError("Impossible d'initialiser CURL");
    }
}






void ApiManager::sendDataHO(int output_index, int hidden_index, float weight)
{
    CURL *curl;
    CURLcode res;
    std::string readBuffer;


    curl = curl_easy_init();
    if (curl)
    {
        // Création de l'objet JSON avec Qt
        QJsonObject json;
        json["output_index"] = output_index;
        json["hidden_index"] = hidden_index;
        json["weight"] = weight;


        QJsonDocument doc(json);
        QByteArray jsonData = doc.toJson();

        struct curl_slist *headers = nullptr;
        headers = curl_slist_append(headers, "Content-Type: application/json");


        curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_msh/post_data.php");
        curl_easy_setopt(curl, CURLOPT_POSTFIELDS, jsonData.constData());
        curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback); // pour capturer la réponse
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);       // buffer pour la réponse
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
        QString response = QString::fromStdString(readBuffer);



        res = curl_easy_perform(curl);


        if (res != CURLE_OK)
        {
            QString error = QString::fromUtf8(curl_easy_strerror(res));
            qDebug() << "Erreur CURL:" << error;
            emit requestError(error);
        }
        else
        {
            QString response = QString::fromStdString(readBuffer);
            qDebug() << "Réponse CURL:" << response;
            emit requestFinished(response);
        }

        curl_slist_free_all(headers);
        curl_easy_cleanup(curl);
    }
    else
    {
        emit requestError("Impossible d'initialiser CURL");
    }
}

void ApiManager::deleteAllEntriesHO()
{
    qDebug() << "deleteAllEntries called";

    CURL* curl = curl_easy_init();
    if (!curl)
    {
        qWarning() << "Failed to initialize CURL!";
        return;
    }

    QString readBuffer;
    curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_msh/get_data.php");
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback_fetch);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);

    CURLcode res = curl_easy_perform(curl);
    if (res != CURLE_OK)
    {
        qWarning() << "curl_easy_perform() failed:" << curl_easy_strerror(res);
        curl_easy_cleanup(curl);
        return;
    }

    curl_easy_cleanup(curl);

    QJsonDocument jsonDoc = QJsonDocument::fromJson(readBuffer.toUtf8());
    if (!jsonDoc.isObject())
    {
        qWarning() << "Invalid JSON format!";
        return;
    }

    QJsonObject rootObj = jsonDoc.object();
    QJsonArray valuesArray = rootObj["data"].toArray();

    for (const QJsonValue& entryVal : valuesArray)
    {
        if (!entryVal.isObject())
            continue;

        QJsonObject entryObj = entryVal.toObject();
        int id = entryObj["id"].toInt();

        // Appeler l'URL de suppression pour chaque ID
        QString deleteUrl = QString("https://nathancampanini.alwaysdata.net/website/methods_msh/delete_data_by_id.php?id=%1").arg(id);

        CURL* deleteCurl = curl_easy_init();
        if (deleteCurl)
        {
            curl_easy_setopt(deleteCurl, CURLOPT_URL, deleteUrl.toUtf8().constData());
            curl_easy_setopt(deleteCurl, CURLOPT_SSL_VERIFYPEER, 0L);
            curl_easy_setopt(deleteCurl, CURLOPT_SSL_VERIFYHOST, 0L);

            CURLcode deleteRes = curl_easy_perform(deleteCurl);
            if (deleteRes != CURLE_OK)
            {
                qWarning() << "Failed to delete ID" << id << ":" << curl_easy_strerror(deleteRes);
            }
            else
            {
                qDebug() << "Successfully deleted ID:" << id;
            }

            curl_easy_cleanup(deleteCurl);
        }
    }

}

void ApiManager::deleteAllEntriesIH()
{
    qDebug() << "deleteAllEntries called";

    CURL* curl = curl_easy_init();
    if (!curl)
    {
        qWarning() << "Failed to initialize CURL!";
        return;
    }

    QString readBuffer;
    curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_msi/get_data.php");
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback_fetch);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
    curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);

    CURLcode res = curl_easy_perform(curl);
    if (res != CURLE_OK)
    {
        qWarning() << "curl_easy_perform() failed:" << curl_easy_strerror(res);
        curl_easy_cleanup(curl);
        return;
    }

    curl_easy_cleanup(curl);

    QJsonDocument jsonDoc = QJsonDocument::fromJson(readBuffer.toUtf8());
    if (!jsonDoc.isObject())
    {
        qWarning() << "Invalid JSON format!";
        return;
    }

    QJsonObject rootObj = jsonDoc.object();
    QJsonArray valuesArray = rootObj["data"].toArray();

    for (const QJsonValue& entryVal : valuesArray)
    {
        if (!entryVal.isObject())
            continue;

        QJsonObject entryObj = entryVal.toObject();
        int id = entryObj["id"].toInt();

        // Appeler l'URL de suppression pour chaque ID
        QString deleteUrl = QString("https://nathancampanini.alwaysdata.net/website/methods_msi/delete_data_by_id.php?id=%1").arg(id);

        CURL* deleteCurl = curl_easy_init();
        if (deleteCurl)
        {
            curl_easy_setopt(deleteCurl, CURLOPT_URL, deleteUrl.toUtf8().constData());
            curl_easy_setopt(deleteCurl, CURLOPT_SSL_VERIFYPEER, 0L);
            curl_easy_setopt(deleteCurl, CURLOPT_SSL_VERIFYHOST, 0L);

            CURLcode deleteRes = curl_easy_perform(deleteCurl);
            if (deleteRes != CURLE_OK)
            {
                qWarning() << "Failed to delete ID" << id << ":" << curl_easy_strerror(deleteRes);
            }
            else
            {
                qDebug() << "Successfully deleted ID:" << id;
            }

            curl_easy_cleanup(deleteCurl);
        }
    }

}
