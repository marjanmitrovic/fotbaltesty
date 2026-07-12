#!/bin/sh
set -eu

max_attempts="${DB_WAIT_ATTEMPTS:-900}"
attempt=1

while [ "$attempt" -le "$max_attempts" ]; do
    if php -r '
        $dsn = getenv("DB_DSN");
        $user = getenv("DB_USER");
        $password = getenv("DB_PASSWORD");
        $options = [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $sslCa = getenv("DB_SSL_CA");
        if ($sslCa) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }
        try {
            $pdo = new PDO($dsn, $user, $password, $options);
            $pdo->query("SELECT 1 FROM users LIMIT 1");
            exit(0);
        } catch (Throwable $e) {
            fwrite(STDERR, "Database/schema not ready: " . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    '; then
        echo "Database and schema are ready."
        break
    fi

    echo "Waiting for database import ($attempt/$max_attempts)..."
    attempt=$((attempt + 1))
    sleep 2
done

if [ "$attempt" -gt "$max_attempts" ]; then
    echo "Database did not become ready in time." >&2
    exit 1
fi

php -r '
    $dsn = getenv("DB_DSN");
    $user = getenv("DB_USER");
    $password = getenv("DB_PASSWORD");
    $email = getenv("DEMO_EMAIL") ?: "demo@fotbaltesty.local";
    $plain = getenv("DEMO_PASSWORD") ?: "demo1234";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    $sslCa = getenv("DB_SSL_CA");
    if ($sslCa) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO($dsn, $user, $password, $options);
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users
        (email, password, role_id, active, newsletter, registrationDate,
         activationDate, deleteDate, lastSignInDate, firstName, surname, idFacr, token)
        VALUES (:email, :password, 3, 1, 0, NOW(), NOW(), NULL, NULL, :firstName, :surname, NULL, NULL)
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            active = 1,
            firstName = VALUES(firstName),
            surname = VALUES(surname)";

    try {
        $pdo->prepare($sql)->execute([
            "email" => $email,
            "password" => $hash,
            "firstName" => "Demo",
            "surname" => "Uživatel",
        ]);
    } catch (Throwable $e) {
        $select = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $select->execute(["email" => $email]);
        $id = $select->fetchColumn();
        if ($id) {
            $pdo->prepare("UPDATE users SET password = :password, active = 1 WHERE id = :id")
                ->execute(["password" => $hash, "id" => $id]);
        } else {
            throw $e;
        }
    }

    echo "Demo account is ready: {$email}\n";
'

exec "$@"
