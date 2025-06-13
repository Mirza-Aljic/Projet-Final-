TEMPLATE = app
CONFIG += console c++17
CONFIG -= app_bundle

QT += core gui widgets

CONFIG += QT

# Configuration pour cURL
LIBS += -LC:/Users/a/Desktop/curl_8_12_1_4_win64_mingw/lib -lcurl
INCLUDEPATH += C:/Users/a/Desktop/curl_8_12_1_4_win64_mingw/include

# Configuration pour nlohmann/json
INCLUDEPATH += C:/Users/a/Desktop/JSON/json-develop/json-develop/include

SOURCES += \
        ProgressWindow.cpp \
        api_manager.cpp \
        main.cpp

HEADERS += \
    ProgressWindow.h \
    api_manager.h \
