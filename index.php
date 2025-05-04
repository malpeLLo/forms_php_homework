<?php
session_start();

// Если запрошена страница с картинкой
if (isset($_GET['funny'])) {
    $funnyUrl = 'https://news.store.rambler.ru/img/db522dbdee37e55a2de67a03a0ba5da5?img-format=auto&img-1-resize=height:400,fit:max&img-2-filter=sharpen';
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Улыбнись!</title>
        <style>
            body { 
                display: flex; 
                justify-content: center; 
                align-items: center; 
                height: 100vh; 
                background: #f0f0f0; 
                margin: 0; 
            }
            img { 
                max-width: 90%; 
                height: auto; 
                border-radius: 8px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.2); 
            }
        </style>
    </head>
    <body>
        <img src="<?= htmlspecialchars($funnyUrl) ?>" alt="Смешная картинка">
    </body>
    </html>
    <?php
    exit;
}

class Database {
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $charset = 'utf8mb4';
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    private $pdo;

    public function __construct() {
        $config = require __DIR__ . '/config.php';
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->db = $config['dbname'];
        $this->user = $config['username'];
        $this->pass = $config['password'];
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $this->options);
        } catch (PDOException $e) {
            exit('Database Connection Failed: ' . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}

class User {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    public function register($name, $email, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :pass)");
        return $stmt->execute(['name' => $name, 'email' => $email, 'pass' => $hash]);
    }

    public function authenticate($email, $password) {
        $stmt = $this->pdo->prepare("SELECT id, name, password FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($row = $stmt->fetch()) {
            if (password_verify($password, $row['password'])) {
                return ['id' => $row['id'], 'name' => $row['name']];
            }
        }
        return false;
    }
}

$action = $_GET['action'] ?? '';
$db = new Database();
$conn = $db->getConnection();
$userModel = new User($conn);
$errors = [];

if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Если пользователь уже залогинен — профиль
if (isset($_SESSION['user'])) {
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Профиль</title></head><body>';
    echo '<h2>Добро пожаловать, ' . htmlspecialchars($_SESSION['user']['name']) . '!</h2>';
    echo '<p><a href="?funny=1">Показать смешную картинку</a></p>';
    echo '<p><a href="?action=logout">Выйти</a></p>';
    echo '</body></html>';
    exit;
}

// Обработка авторизации
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $errors[] = 'Заполните все поля.';
    } else {
        $user = $userModel->authenticate($email, $password);
        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Неверный email или пароль.';
        }
    }
}

// Обработка регистрации
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($name)) $errors[] = 'Введите имя.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный email.';
    if (strlen($password) < 8) $errors[] = 'Пароль должен быть не менее 8 символов.';
    if (empty($errors)) {
        if ($userModel->emailExists($email)) {
            $errors[] = 'Пользователь с таким email уже зарегистрирован.';
        } elseif ($userModel->register($name, $email, $password)) {
            header('Location: index.php?action=login');
            exit;
        } else {
            $errors[] = 'Ошибка при сохранении данных.';
        }
    }
}

// Форма регистрации и авторизации
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'login' ? 'Авторизация' : 'Регистрация'; ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f4f4f4; 
        }
        .container { 
            max-width: 400px; 
            margin: 50px auto; 
            padding: 20px; background: #fff; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }
        input { 
            width: 100%; 
            padding: 10px; 
            margin: 5px 0 15px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        button { 
            width: 100%; 
            padding: 10px; 
            background: #28a745; 
            color: #fff; border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        button:hover { 
            background: #218838; 
        }
        .error { 
            color: #dc3545; 
        }
        .links { 
            text-align: center; 
            margin-top: 10px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $action === 'login' ? 'Авторизация' : 'Регистрация'; ?></h2>
        <?php if ($errors): ?>
            <div class="error"><ul><?php foreach ($errors as $err): ?><li><?=htmlspecialchars($err)?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <?php if ($action === 'login'): ?>
            <form method="POST" action="?action=login">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit">Войти</button>
            </form>
            <div class="links"><a href="index.php?action=register">Регистрация</a></div>
        <?php else: ?>
            <form method="POST" action="?action=register">
                <input type="text" name="name" placeholder="Имя" required value="<?=htmlspecialchars($name ?? '')?>">
                <input type="email" name="email" placeholder="Email" required value="<?=htmlspecialchars($email ?? '')?>">
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit">Зарегистрироваться</button>
            </form>
            <div class="links"><a href="index.php?action=login">Уже есть аккаунт? Войти</a></div>
        <?php endif; ?>
    </div>
</body>
</html>
