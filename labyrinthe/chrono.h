#ifndef CHRONO_HPP
#define CHRONO_HPP

#include <chrono>

class Chronometre {
public:
    Chronometre();
    void demarrer();
    void arreter();
    double tempsEcoule() const;

private:
    std::chrono::time_point<std::chrono::high_resolution_clock> debut;
    std::chrono::time_point<std::chrono::high_resolution_clock> fin;
    bool enCours;
};

#endif // CHRONO_HPP
