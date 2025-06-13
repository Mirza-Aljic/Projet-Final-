#include "chrono.h"

Chronometre::Chronometre() : enCours(false) {}

void Chronometre::demarrer() {
    if (!enCours) {
        debut = std::chrono::high_resolution_clock::now();
        enCours = true;
    }
}

void Chronometre::arreter() {
    if (enCours) {
        fin = std::chrono::high_resolution_clock::now();
        enCours = false;
    }
}

double Chronometre::tempsEcoule() const {
    if (enCours) {
        auto maintenant = std::chrono::high_resolution_clock::now();
        return std::chrono::duration<double>(maintenant - debut).count();
    } else {
        return std::chrono::duration<double>(fin - debut).count();
    }
}
