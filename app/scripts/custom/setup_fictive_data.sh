#!/bin/bash
###############################################################################
# Script de création automatique des données fictives pour Dolibarr A2S
#
# Ce script exécute dans l'ordre tous les scripts de création de données:
# 1. Entrepôts
# 2. Extrafields produits
# 3. Produits paramédicaux avec stocks
# 4. Clients fictifs
#
# Usage:
#   ./setup_fictive_data.sh [--skip-extrafields] [--skip-warehouses]
#
# Options:
#   --skip-extrafields  : Passer la création des extrafields (si déjà créés)
#   --skip-warehouses   : Passer la création des entrepôts (si déjà créés)
#   --help             : Afficher cette aide
###############################################################################

set -e  # Arrêter en cas d'erreur

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKIP_EXTRAFIELDS=0
SKIP_WAREHOUSES=0

# Fonction d'affichage
print_header() {
    echo -e "\n${BLUE}=================================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}=================================================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Fonction d'aide
show_help() {
    cat << EOF
Usage: $0 [OPTIONS]

Ce script crée automatiquement toutes les données fictives pour tester A2S.

Options:
    --skip-extrafields    Passer la création des extrafields (si déjà créés)
    --skip-warehouses     Passer la création des entrepôts (si déjà créés)
    --help               Afficher cette aide

Exemples:
    $0                                    # Créer toutes les données
    $0 --skip-extrafields                 # Créer sans les extrafields
    $0 --skip-extrafields --skip-warehouses  # Créer uniquement produits et clients

EOF
}

# Parse des arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-extrafields)
            SKIP_EXTRAFIELDS=1
            shift
            ;;
        --skip-warehouses)
            SKIP_WAREHOUSES=1
            shift
            ;;
        --help|-h)
            show_help
            exit 0
            ;;
        *)
            print_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Vérification de l'environnement
print_header "Vérification de l'environnement"

if [ ! -f "$SCRIPT_DIR/create_warehouses.php" ]; then
    print_error "Scripts non trouvés dans $SCRIPT_DIR"
    exit 1
fi

print_success "Scripts trouvés"

# Vérifier PHP CLI
if ! command -v php &> /dev/null; then
    print_error "PHP CLI non trouvé. Installez PHP ou exécutez ce script dans le conteneur Docker."
    exit 1
fi

print_success "PHP CLI disponible ($(php -v | head -n 1))"

# Début de l'installation
print_header "Début de la création des données fictives"
print_info "Date: $(date '+%Y-%m-%d %H:%M:%S')"
print_info "Répertoire: $SCRIPT_DIR"
echo ""

# Étape 1: Création des entrepôts
if [ $SKIP_WAREHOUSES -eq 0 ]; then
    print_header "ÉTAPE 1/4 - Création des entrepôts"
    if php "$SCRIPT_DIR/create_warehouses.php"; then
        print_success "Entrepôts créés avec succès"
    else
        print_error "Échec de la création des entrepôts"
        exit 1
    fi
else
    print_warning "ÉTAPE 1/4 - Création des entrepôts IGNORÉE (--skip-warehouses)"
fi

# Étape 2: Création des extrafields
if [ $SKIP_EXTRAFIELDS -eq 0 ]; then
    print_header "ÉTAPE 2/4 - Création des extrafields produits"
    if [ -f "$SCRIPT_DIR/create_product_extrafields.php" ]; then
        if php "$SCRIPT_DIR/create_product_extrafields.php"; then
            print_success "Extrafields créés avec succès"
        else
            print_error "Échec de la création des extrafields"
            exit 1
        fi
    else
        print_warning "Script create_product_extrafields.php non trouvé, ignoré"
    fi
else
    print_warning "ÉTAPE 2/4 - Création des extrafields IGNORÉE (--skip-extrafields)"
fi

# Étape 3: Création des produits paramédicaux
print_header "ÉTAPE 3/4 - Création des produits paramédicaux avec stocks"
if php "$SCRIPT_DIR/create_fictive_paramedical_products.php"; then
    print_success "Produits paramédicaux créés avec succès"
else
    print_error "Échec de la création des produits"
    exit 1
fi

# Étape 4: Création des clients
print_header "ÉTAPE 4/4 - Création des clients fictifs"
if php "$SCRIPT_DIR/create_fictive_clients.php"; then
    print_success "Clients fictifs créés avec succès"
else
    print_error "Échec de la création des clients"
    exit 1
fi

# Résumé final
print_header "INSTALLATION TERMINÉE"

cat << EOF
${GREEN}✓ Toutes les données fictives ont été créées avec succès !${NC}

${BLUE}📊 Données créées:${NC}
  • 7 entrepôts répartis dans tout le Maroc
  • 17 produits paramédicaux avec stocks
  • 14 clients (11 B2B + 3 B2C)
  • 11 contacts professionnels
  • Mouvements de stock automatiques

${BLUE}🎯 Prochaines étapes:${NC}
  1. Connectez-vous à Dolibarr
  2. Vérifiez les produits créés (Menu Produits/Services)
  3. Vérifiez les clients (Menu Tiers)
  4. Vérifiez les stocks (Menu Stocks)
  5. Créez des commandes de test

${BLUE}📚 Documentation:${NC}
  Consultez README_FICTIVE_DATA.md pour plus de détails

${BLUE}⚡ Cas de test suggérés:${NC}
  • Commande pharmacie avec remise 5%
  • Commande hôpital avec gros volume (remise 18%)
  • Vente particulier en paiement immédiat
  • Gestion multi-entrepôts
  • Suivi des créances clients

${GREEN}Bon test avec A2S ! 🚀${NC}

EOF

exit 0
