#!/bin/bash

#############################################################################
# Script d'import des articles dans Dolibarr
#
# Ce script automatise le processus complet d'import :
# 1. Création des extrafields nécessaires
# 2. Import des articles depuis le fichier Excel
#
# Usage:
#   ./import_articles.sh
#   ./import_articles.sh --skip-extrafields
#
# Options:
#   --skip-extrafields : Ne pas créer les extrafields (si déjà créés)
#############################################################################

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Répertoires
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_DIR="/var/www/html/python/import_data"
INPUT_FILE="articles.xls"
MAPPING_FILE="articles_mapping_dolibarr.xlsx"

# Options
SKIP_EXTRAFIELDS=false

# Parser les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-extrafields)
            SKIP_EXTRAFIELDS=true
            shift
            ;;
        -h|--help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --skip-extrafields    Ne pas créer les extrafields"
            echo "  -h, --help           Afficher cette aide"
            exit 0
            ;;
        *)
            echo -e "${RED}Option inconnue: $1${NC}"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}       Import des articles dans Dolibarr${NC}"
echo -e "${BLUE}============================================================${NC}"
echo ""

# Vérifier que les fichiers existent
echo -e "${YELLOW}Vérification des fichiers...${NC}"

if [ ! -f "$INPUT_FILE" ]; then
    echo -e "${RED}✗ Fichier de données introuvable: $INPUT_FILE${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Fichier de données trouvé${NC}"

if [ ! -f "$MAPPING_FILE" ]; then
    echo -e "${RED}✗ Fichier de mapping introuvable: $MAPPING_FILE${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Fichier de mapping trouvé${NC}"
echo ""

# Étape 1: Créer les extrafields (si demandé)
if [ "$SKIP_EXTRAFIELDS" = false ]; then
    echo -e "${BLUE}------------------------------------------------------------${NC}"
    echo -e "${BLUE}Étape 1/2: Création des extrafields${NC}"
    echo -e "${BLUE}------------------------------------------------------------${NC}"
    echo ""

    php "$SCRIPT_DIR/create_product_extrafields.php"
    EXTRAFIELDS_EXIT_CODE=$?

    if [ $EXTRAFIELDS_EXIT_CODE -ne 0 ]; then
        echo ""
        echo -e "${RED}✗ Erreur lors de la création des extrafields${NC}"
        echo -e "${YELLOW}Vous pouvez ignorer cette erreur si les extrafields existent déjà${NC}"
        echo -e "${YELLOW}Pour continuer sans créer les extrafields, utilisez: $0 --skip-extrafields${NC}"
        exit $EXTRAFIELDS_EXIT_CODE
    fi
    echo ""
else
    echo -e "${YELLOW}⊙ Création des extrafields ignorée (--skip-extrafields)${NC}"
    echo ""
fi

# Étape 2: Import des articles
echo -e "${BLUE}------------------------------------------------------------${NC}"
if [ "$SKIP_EXTRAFIELDS" = false ]; then
    echo -e "${BLUE}Étape 2/2: Import des articles${NC}"
else
    echo -e "${BLUE}Import des articles${NC}"
fi
echo -e "${BLUE}------------------------------------------------------------${NC}"
echo ""

php "$SCRIPT_DIR/import_articles_from_excel.php" "$INPUT_FILE" "$MAPPING_FILE"
IMPORT_EXIT_CODE=$?

echo ""
if [ $IMPORT_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}============================================================${NC}"
    echo -e "${GREEN}✓ Import terminé avec succès !${NC}"
    echo -e "${GREEN}============================================================${NC}"
    echo ""
    echo -e "${YELLOW}Prochaines étapes :${NC}"
    echo "  1. Vérifiez les produits importés dans Dolibarr"
    echo "  2. Vérifiez les catégories créées"
    echo "  3. Ajoutez les images des produits si nécessaire"
    echo "  4. Configurez les prix multiples si besoin"
    exit 0
else
    echo -e "${RED}============================================================${NC}"
    echo -e "${RED}✗ Erreur lors de l'import${NC}"
    echo -e "${RED}============================================================${NC}"
    echo ""
    echo -e "${YELLOW}Vérifiez les erreurs affichées ci-dessus${NC}"
    exit $IMPORT_EXIT_CODE
fi
