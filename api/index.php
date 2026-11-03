<?php

declare(strict_types=1);

session_start();
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/database.php";

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function body(): array
{
    $decoded = json_decode(file_get_contents("php://input"), true);
    return is_array($decoded) ? $decoded : [];
}

function requireUser(): array
{
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        respond(["error" => "Authentication required."], 401);
    }
    return [
        "id" => (int) $_SESSION["user_id"],
        "full_name" => (string) ($_SESSION["full_name"] ?? ""),
        "email" => (string) ($_SESSION["email"] ?? ""),
        "role" => (string) $_SESSION["role"],
    ];
}

function rows(PDO $pdo, string $table): array
{
    $queries = [
        "students" => "SELECT s.id, u.full_name, s.student_number, u.email, c.class_name FROM students s JOIN users u ON u.id = s.user_id LEFT JOIN student_classes sc ON sc.student_id = s.id LEFT JOIN classes c ON c.id = sc.class_id ORDER BY u.full_name",
        "teachers" => "SELECT t.id, u.full_name, t.employee_number, t.specialization, u.email FROM teachers t JOIN users u ON u.id = t.user_id ORDER BY u.full_name",
        "parents" => "SELECT p.id, u.full_name, p.parent_number, p.phone, u.email FROM parents p JOIN users u ON u.id = p.user_id ORDER BY u.full_name",
        "classes" => "SELECT id, class_name, class_code, description FROM classes ORDER BY class_name",
        "subjects" => "SELECT id, subject_name, subject_code, description FROM subjects ORDER BY subject_name",
    ];
    if (!isset($queries[$table])) respond(["error" => "Unknown resource."], 400);
    return $pdo->query($queries[$table])->fetchAll(PDO::FETCH_ASSOC);
}

$action = $_GET["action"] ?? "";

try {
    if ($action === "login" && $_SERVER["REQUEST_METHOD"] === "POST") {
        $input = body();
        $credential = trim((string) ($input["credential"] ?? ""));
        $password = (string) ($input["password"] ?? "");
        if ($credential === "" || $password === "") respond(["error" => "Enter your credential and password."], 422);

        $queries = [
            "SELECT id, full_name, email, password, role, status FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1",
            "SELECT u.id, u.full_name, u.email, u.password, u.role, u.status FROM students s JOIN users u ON u.id = s.user_id WHERE UPPER(s.student_number) = UPPER(?) AND u.role = 'student' LIMIT 1",
            "SELECT u.id, u.full_name, u.email, u.password, u.role, u.status FROM teachers t JOIN users u ON u.id = t.user_id WHERE UPPER(t.employee_number) = UPPER(?) AND u.role = 'teacher' LIMIT 1",
            "SELECT u.id, u.full_name, u.email, u.password, u.role, u.status FROM parents p JOIN users u ON u.id = p.user_id WHERE UPPER(p.parent_number) = UPPER(?) AND u.role = 'parent' LIMIT 1",
        ];
        $user = false;
        foreach ($queries as $query) {
            $statement = $pdo->prepare($query);
            $statement->execute([$credential]);
            $user = $statement->fetch();
            if ($user) break;
        }
        if (!$user || !password_verify($password, $user["password"])) respond(["error" => "Invalid login credential or password."], 401);
        if ($user["status"] !== "active") respond(["error" => "Your account is inactive."], 403);
        session_regenerate_id(true);
        $_SESSION["user_id"] = (int) $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];
        respond(["user" => requireUser()]);
    }

    if ($action === "logout") {
        session_unset();
        session_destroy();
        respond(["ok" => true]);
    }

    $user = requireUser();
    if ($action === "me") respond(["user" => $user]);

    if ($action === "dashboard") {
        $stats = [];
        if ($user["role"] === "admin") {
            foreach (["students", "teachers", "parents", "classes", "subjects"] as $table) {
                $stats[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            }
        } elseif ($user["role"] === "teacher") {
            $statement = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects ts JOIN teachers t ON t.id = ts.teacher_id WHERE t.user_id = ?");
            $statement->execute([$user["id"]]);
            $stats["subjects"] = (int) $statement->fetchColumn();
            $stats["attendance"] = 0;
        } elseif ($user["role"] === "student") {
            $statement = $pdo->prepare("SELECT COUNT(*) FROM student_classes sc JOIN students s ON s.id = sc.student_id WHERE s.user_id = ?");
            $statement->execute([$user["id"]]);
            $stats["subjects"] = (int) $statement->fetchColumn();
            $stats["attendance"] = 0;
        } else {
            $statement = $pdo->prepare("SELECT COUNT(*) FROM student_parents sp JOIN parents p ON p.id = sp.parent_id WHERE p.user_id = ?");
            $statement->execute([$user["id"]]);
            $stats["subjects"] = (int) $statement->fetchColumn();
            $stats["attendance"] = 0;
        }
        respond(["stats" => $stats]);
    }

    if ($action === "resource") {
        $resource = (string) ($_GET["name"] ?? "");
        if ($user["role"] === "admin") {
            respond(["rows" => rows($pdo, $resource)]);
        }
        if ($resource === "subjects" && $user["role"] === "teacher") {
            $statement = $pdo->prepare("SELECT DISTINCT s.id, s.subject_name, s.subject_code, s.description FROM teacher_subjects ts JOIN teachers t ON t.id = ts.teacher_id JOIN subjects s ON s.id = ts.subject_id WHERE t.user_id = ? ORDER BY s.subject_name");
            $statement->execute([$user["id"]]);
            respond(["rows" => $statement->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($resource === "subjects" && $user["role"] === "student") {
            $statement = $pdo->prepare("SELECT DISTINCT s.id, s.subject_name, s.subject_code, c.class_name FROM student_classes sc JOIN students st ON st.id = sc.student_id JOIN teacher_classes tc ON tc.class_id = sc.class_id JOIN teacher_subjects ts ON ts.teacher_id = tc.teacher_id JOIN subjects s ON s.id = ts.subject_id JOIN classes c ON c.id = sc.class_id WHERE st.user_id = ? ORDER BY s.subject_name");
            $statement->execute([$user["id"]]);
            respond(["rows" => $statement->fetchAll(PDO::FETCH_ASSOC)]);
        }
        respond(["rows" => []]);
    }

    respond(["error" => "Unknown action."], 404);
} catch (PDOException | JsonException $exception) {
    error_log("React API error: " . $exception->getMessage());
    respond(["error" => "The request could not be completed."], 500);
}
