#!/bin/sh
# Démarrage du conteneur web : attend MariaDB, règle une fois les mots de passe
# des comptes de démonstration, puis lance Apache.
set -e

cd /var/www/html

# volumes montés : les droits doivent rester à www-data
mkdir -p storage/cache public/uploads
chown -R www-data:www-data storage public/uploads

echo "Attente de MariaDB..."
i=0
until php -r 'try { new PDO("mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: 3306) . ";dbname=" . getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
        echo "MariaDB ne répond pas après 2 minutes, Apache démarre quand même." >&2
        break
    fi
    sleep 2
done

# une seule fois par volume : sinon un mot de passe changé sur le site serait écrasé
if [ ! -f storage/.demo-passwords-done ]; then
    if php docker/demo-passwords.php; then
        touch storage/.demo-passwords-done
        chown www-data:www-data storage/.demo-passwords-done
    fi
fi

exec "$@"
