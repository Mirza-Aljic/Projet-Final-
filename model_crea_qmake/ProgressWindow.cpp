#ifndef PROGRESSWINDOW_CPP
#define PROGRESSWINDOW_CPP

#include "ProgressWindow.h"
#include <QVBoxLayout>

ProgressWindow::ProgressWindow(QWidget *parent):QDialog(parent)
{
    progressBar = new QProgressBar(this);
    progressBar -> setMinimum(0);

    QVBoxLayout *layout = new QVBoxLayout(this);
    layout -> addWidget(progressBar);

    setWindowTitle("training ...");
}

void ProgressWindow::setMaximum(int max){
    progressBar -> setMaximum(max);
}

void ProgressWindow::updateProgress(int value)
{
    progressBar -> setValue(value);
}

#endif
