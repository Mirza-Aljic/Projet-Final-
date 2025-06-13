#include <api_manager.h>

static size_t WriteCallback_send(void *contents, size_t size, size_t nmemb, void *userp)
{
    ((std::string *)userp)->append((char *)contents, size * nmemb);
    return size * nmemb;
}


size_t WriteCallback(void* contents, size_t size, size_t nmemb, void* userp)
{
    size_t totalSize = size * nmemb;
    QString* buffer = static_cast<QString*>(userp);
    buffer->append(QString::fromUtf8(static_cast<char*>(contents), static_cast<int>(totalSize)));
    return totalSize;
}

ApiManager::ApiManager(QObject *parent) : QObject(parent) {}

void ApiManager::sendSimulationData(int nombre_coups, int &duree_simulation, const QString &modele_simulation)
{
    CURL *curl;
    CURLcode res;
    std::string readBuffer;

    int min = 0;
    int sec = 0;

    curl = curl_easy_init();
    if (curl)
    {
        min = duree_simulation/60;
        sec = duree_simulation %60;
        // Création de l'objet JSON avec Qt
        QJsonObject json;
        json["nombre_coups"] = nombre_coups;
        json["duree_simulation"] = QString("%1min %2s").arg(min).arg(sec);
        json["modele_simulation"] = modele_simulation;

        QJsonDocument doc(json);
        QByteArray jsonData = doc.toJson();

        struct curl_slist *headers = nullptr;
        headers = curl_slist_append(headers, "Content-Type: application/json");

        curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_simulation/post_data.php");
        curl_easy_setopt(curl, CURLOPT_POSTFIELDS, jsonData.constData());
        curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback_send); // pour capturer la réponse
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);       // buffer pour la réponse
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
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



QVector<QVector<double>> ApiManager::getHO()
{
    const int HIDDEN_SIZE = 20;

    // Une colonne, 20 lignes
    QVector<QVector<double>> hoMatrix(HIDDEN_SIZE, QVector<double>(1, 0.0));

    qDebug() << "getHO called";

    CURL* curl = curl_easy_init();
    if (curl)
    {
        QString readBuffer;
        curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_msh/get_data.php");
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
        CURLcode res = curl_easy_perform(curl);
        if (res != CURLE_OK)
        {
            qWarning() << "curl_easy_perform() failed:" << curl_easy_strerror(res);
            curl_easy_cleanup(curl);
            return hoMatrix;
        }

        curl_easy_cleanup(curl);

        QJsonDocument jsonDoc = QJsonDocument::fromJson(readBuffer.toUtf8());
        if (!jsonDoc.isObject())
        {
            qWarning() << "Invalid JSON format!";
            return hoMatrix;
        }

        QJsonArray valuesArray = jsonDoc.object()["data"].toArray();

        for (const QJsonValue& entryVal : valuesArray)
        {
            if (!entryVal.isObject())
                continue;

            QJsonObject obj = entryVal.toObject();
            int hiddenIndex = obj["hidden_index"].toInt();
            double weight = obj["weight"].toDouble();

            if (hiddenIndex >= 0 && hiddenIndex < HIDDEN_SIZE)
            {
                hoMatrix[hiddenIndex][0] = weight;  // Une seule colonne
            }
        }
    }
    else
    {
        qWarning() << "Failed to initialize CURL!";
        return hoMatrix;
    }

    // Affichage ligne par ligne : 20 lignes × 1 colonne
    qDebug() << "Matrice HO [hidden][output] (20x1) :";
    for (int h = 0; h < hoMatrix.size(); ++h)
    {
        qDebug() << "Hidden" << h << ":" << QString::number(hoMatrix[h][0], 'f', 10);
    }

    return hoMatrix;
}






QVector<QVector<double>> ApiManager::getIH()
{
    qDebug() << "getIH called";

    const int INPUT_SIZE = 7;
    const int HIDDEN_SIZE = 20;

    // Initialisation de la matrice HIDDEN x INPUT (20 x 7)
    QVector<QVector<double>> weightMatrix(HIDDEN_SIZE, QVector<double>(INPUT_SIZE, 0.0));

    CURL* curl = curl_easy_init();
    if (curl)
    {
        QString readBuffer;
        curl_easy_setopt(curl, CURLOPT_URL, "https://nathancampanini.alwaysdata.net/website/methods_msi/get_data.php");
        curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, WriteCallback);
        curl_easy_setopt(curl, CURLOPT_WRITEDATA, &readBuffer);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);
        curl_easy_setopt(curl, CURLOPT_SSL_VERIFYHOST, 0L);
        CURLcode res = curl_easy_perform(curl);
        if (res != CURLE_OK)
        {
            qWarning() << "curl_easy_perform() failed:" << curl_easy_strerror(res);
            curl_easy_cleanup(curl);
            return weightMatrix;
        }

        curl_easy_cleanup(curl);

        QJsonDocument jsonDoc = QJsonDocument::fromJson(readBuffer.toUtf8());
        if (!jsonDoc.isObject())
        {
            qWarning() << "Invalid JSON format!";
            return weightMatrix;
        }

        QJsonArray valuesArray = jsonDoc.object()["data"].toArray();

        for (const QJsonValue& entryVal : valuesArray)
        {
            if (!entryVal.isObject())
                continue;

            QJsonObject obj = entryVal.toObject();
            int inputIndex = obj["input_index"].toInt();   // ✅ Corrigé ici
            int hiddenIndex = obj["hidden_index"].toInt(); // ✅ Utilisation correcte
            double weight = obj["weight"].toDouble();

            if (inputIndex >= 0 && inputIndex < INPUT_SIZE &&
                hiddenIndex >= 0 && hiddenIndex < HIDDEN_SIZE)
            {
                weightMatrix[hiddenIndex][inputIndex] = weight;
            }
        }
    }
    else
    {
        qWarning() << "Failed to initialize CURL!";
        return weightMatrix;
    }

    // 🔎 Affichage de la matrice complète : 20 lignes (hidden), 7 colonnes (inputs)
    qDebug() << "Matrice IH [hidden][input] (20x7) :";
    for (int h = 0; h < weightMatrix.size(); ++h)
    {
        QStringList rowStr;
        for (int i = 0; i < weightMatrix[h].size(); ++i)
        {
            rowStr << QString::number(weightMatrix[h][i], 'f', 10);
        }
        qDebug() << "Hidden" << h << ":" << rowStr.join(", ");
    }

    return weightMatrix;
}
