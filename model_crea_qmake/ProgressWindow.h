#ifndef PROGRESSWINDOW_H
#define PROGRESSWINDOW_H

#include<QDialog>
#include<QProgressBar>


class ProgressWindow : public QDialog
{
    Q_OBJECT

public:
    ProgressWindow(QWidget *parent = nullptr);
    void setMaximum(int max);

public slots:
    void updateProgress(int value);

private:
    QProgressBar *progressBar;
};

#endif // PROGRESSWINDOW_H
