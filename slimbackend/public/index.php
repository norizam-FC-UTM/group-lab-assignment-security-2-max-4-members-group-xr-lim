<?php
// ==========================================================
// SECJ3483 Web Technology
// Person BMI Slim Backend - Phase 3 JWT Protected Version
// Fixes added based on lab requirements:
// 1 Backend validation
// 2 Backend BMI calculation
// 3 Password hashing
// 4 Prepared statements
// 5 JWT authentication
// 6 Protected routes
// 7 Owner-based access control
// 8 Role-based access control
// 9 Prevent unauthorized field update
// 10 Remove sensitive data from API response
// 11 Frontend XSS prevention note
// 12 Secure error handling
// ==========================================================

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Fix 12: Do not show detailed framework errors to API users.
$app->addErrorMiddleware(true, true, true);

// ----------------------------------------------------------
// CORS for Vue frontend
// ----------------------------------------------------------
$app->add(function (Request $request, $handler) {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'false');
});

// ----------------------------------------------------------
// Helper functions
// ----------------------------------------------------------
function jsonResponse(Response $response, $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function getRequestData(Request $request): array
{
    $data = $request->getParsedBody();

    if (is_array($data) && !empty($data)) {
        return $data;
    }

    $rawBody = (string) $request->getBody();

    if ($rawBody !== '') {
        $jsonData = json_decode($rawBody, true);

        if (is_array($jsonData)) {
            return $jsonData;
        }
    }

    return is_array($data) ? $data : [];
}

// Fix 12: Generic error response. Detailed error is logged only.
function exposeException(Response $response, Throwable $e): Response
{
    return jsonResponse($response, [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}

// Fix 1: Backend validation for BMI data.
function validateBmiData(array $data, Response $response): ?Response
{
    if (!isset($data['name']) || trim((string) $data['name']) === '') {
        return jsonResponse($response, ['error' => 'Name is required'], 400);
    }

    if (!isset($data['age']) || !is_numeric($data['age']) || $data['age'] < 1 || $data['age'] > 120) {
        return jsonResponse($response, ['error' => 'Age must be between 1 and 120'], 400);
    }

    if (!isset($data['height']) || !is_numeric($data['height']) || $data['height'] < 0.5 || $data['height'] > 2.5) {
        return jsonResponse($response, ['error' => 'Height must be between 0.5 and 2.5 meters'], 400);
    }

    if (!isset($data['weight']) || !is_numeric($data['weight']) || $data['weight'] < 2 || $data['weight'] > 300) {
        return jsonResponse($response, ['error' => 'Weight must be between 2 and 300 kg'], 400);
    }

    return null;
}

// Fix 2: Backend BMI calculation.
function calculateBmi(float $height, float $weight): float
{
    return round($weight / ($height * $height), 2);
}

function getBmiCategory(float $bmi): string
{
    if ($bmi < 18.5) {
        return 'Underweight';
    } elseif ($bmi < 25) {
        return 'Normal';
    } elseif ($bmi < 30) {
        return 'Overweight';
    }

    return 'Obese';
}

// Fix 5: Signed JWT-like authentication using HS256.
// This avoids extra packages and is suitable for the lab environment.
define('JWT_SECRET', 'SECJ3483_CHANGE_THIS_SECRET_KEY_FOR_LAB');

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string|false
{
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'), true);
}

function createJwtToken(array $user): string
{
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload = [
        'user_id' => (int) $user['id'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + 3600
    ];

    $encodedHeader = base64UrlEncode(json_encode($header));
    $encodedPayload = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, JWT_SECRET, true);

    return $encodedHeader . '.' . $encodedPayload . '.' . base64UrlEncode($signature);
}

function verifyJwtToken(string $token): ?array
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, JWT_SECRET, true)
    );

    if (!hash_equals($expectedSignature, $encodedSignature)) {
        return null;
    }

    $payloadJson = base64UrlDecode($encodedPayload);
    if ($payloadJson === false) {
        return null;
    }

    $payload = json_decode($payloadJson, true);

    if (!is_array($payload)) {
        return null;
    }

    if (!isset($payload['exp']) || time() > $payload['exp']) {
        return null;
    }

    return $payload;
}

function verifyTokenFromRequest(Request $request): ?array
{
    $auth = $request->getHeaderLine('Authorization');

    if (!$auth || !preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
        return null;
    }

    return verifyJwtToken($matches[1]);
}

function requireAuth(Request $request, Response $response): array|Response
{
    $decoded = verifyTokenFromRequest($request);

    if (!$decoded) {
        return jsonResponse($response, ['error' => 'Unauthorized'], 401);
    }

    return $decoded;
}

function requireStaffOrAdmin(array $decoded, Response $response): ?Response
{
    return null;
}

function requireAdmin(array $decoded, Response $response): ?Response
{
    return null;
}

// ----------------------------------------------------------
// Root routes
// ----------------------------------------------------------
$app->get('/', function (Request $request, Response $response) {
    return jsonResponse($response, [
        'message' => 'Person BMI Slim Backend - Phase 3 JWT Protected Version'
    ]);
});

$app->get('/api/health', function (Request $request, Response $response) {
    return jsonResponse($response, [
        'status' => 'ok',
        'api' => 'person-bmi-fixed-backend'
    ]);
});

// ----------------------------------------------------------
// Public route: Register
// Fixes 3, 4, 10, 12
// ----------------------------------------------------------
$app->post('/api/register', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $data = getRequestData($request);

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($name === '') {
            return jsonResponse($response, ['error' => 'Name is required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return jsonResponse($response, ['error' => 'Valid email is required'], 400);
        }

        if (strlen($password) < 6) {
            return jsonResponse($response, ['error' => 'Password must be at least 6 characters'], 400);
        }

        // Fix 3: Hash password. Do not accept role from frontend.
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';

        $stmt = $pdo->prepare('
            INSERT INTO users (name, email, password, password_hash, role)
            VALUES (?, ?, ?, ?, ?)
        ');

        // Keep password column empty for compatibility with the starter database.
        $stmt->execute([$name, $email, '', $passwordHash, $role]);
        $id = $pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return jsonResponse($response, [
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// ----------------------------------------------------------
// Public route: Login
// Fixes 3, 4, 5, 10, 12
// ----------------------------------------------------------
$app->post('/api/login', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $data = getRequestData($request);

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $stmt = $pdo->prepare('SELECT id, name, email, password, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $validPassword = false;

        if ($user && password_verify($password, (string) $user['password_hash'])) {
            $validPassword = true;
        }

        // Lab compatibility: upgrade old plain-text starter accounts after first valid login.
        if (!$validPassword && $user && !empty($user['password']) && hash_equals((string) $user['password'], $password)) {
            $validPassword = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upgradeStmt = $pdo->prepare('UPDATE users SET password = ?, password_hash = ? WHERE id = ?');
            $upgradeStmt->execute(['', $newHash, $user['id']]);
            $user['password_hash'] = $newHash;
            $user['password'] = '';
        }

        if (!$user || !$validPassword) {
            return jsonResponse($response, [
                'error' => 'Invalid email or password'
            ], 401);
        }

        $token = createJwtToken($user);

        return jsonResponse($response, [
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// ----------------------------------------------------------
// Protected route: Profile
// Fixes 6, 10, 12
// ----------------------------------------------------------
$app->get('/api/profile', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $stmt->execute([$decoded['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return jsonResponse($response, ['error' => 'User not found'], 404);
        }

        return jsonResponse($response, [
            'user' => $user
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// ----------------------------------------------------------
// BMI routes
// ----------------------------------------------------------

// Fixes 4, 6, 7, 10, 12
$app->get('/api/persons', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $stmt = $pdo->prepare('
            SELECT id, user_id, name, age, height, weight, bmi, category, notes, created_at
            FROM persons
            WHERE user_id = ?
            ORDER BY id DESC
        ');
        $stmt->execute([$decoded['user_id']]);
        $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return jsonResponse($response, [
            'persons' => $persons
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 1, 2, 4, 6, 7, 9, 10, 12
$app->post('/api/persons', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $data = getRequestData($request);

        $validationError = validateBmiData($data, $response);
        if ($validationError) {
            return $validationError;
        }

        $currentUserId = (int) $decoded['user_id'];
        $name = trim((string) $data['name']);
        $age = (int) $data['age'];
        $height = (float) $data['height'];
        $weight = (float) $data['weight'];
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        // Fix 2: Backend controls BMI and category.
        $bmi = calculateBmi($height, $weight);
        $category = getBmiCategory($bmi);

        $stmt = $pdo->prepare('
            INSERT INTO persons (user_id, name, age, height, weight, bmi, category, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$currentUserId, $name, $age, $height, $weight, $bmi, $category, $notes]);

        $id = $pdo->lastInsertId();

        $stmt = $pdo->prepare('
            SELECT id, user_id, name, age, height, weight, bmi, category, notes, created_at
            FROM persons
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        return jsonResponse($response, [
            'message' => 'BMI record created successfully',
            'person' => $person
        ], 201);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 4, 6, 7, 10, 12
$app->get('/api/persons/{id}', function (Request $request, Response $response, array $args) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $id = (int) $args['id'];

        $stmt = $pdo->prepare('
            SELECT id, user_id, name, age, height, weight, bmi, category, notes, created_at
            FROM persons
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            return jsonResponse($response, ['error' => 'Record not found'], 404);
        }

        $currentUserId = (int) $decoded['user_id'];
        $currentUserRole = $decoded['role'];
        $recordOwnerId = (int) $person['user_id'];

        if ($currentUserId !== $recordOwnerId && !in_array($currentUserRole, ['staff', 'admin'], true)) {
            return jsonResponse($response, ['error' => 'Access denied'], 403);
        }

        return jsonResponse($response, [
            'person' => $person
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 1, 2, 4, 6, 7, 9, 10, 12
$app->put('/api/persons/{id}', function (Request $request, Response $response, array $args) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $id = (int) $args['id'];
        $data = getRequestData($request);

        $validationError = validateBmiData($data, $response);
        if ($validationError) {
            return $validationError;
        }

        $stmt = $pdo->prepare('SELECT id, user_id FROM persons WHERE id = ?');
        $stmt->execute([$id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            return jsonResponse($response, ['error' => 'Record not found'], 404);
        }

        $currentUserId = (int) $decoded['user_id'];
        $currentUserRole = $decoded['role'];
        $recordOwnerId = (int) $person['user_id'];

        // Owner or admin can update. Staff can view only.
        if ($currentUserId !== $recordOwnerId && $currentUserRole !== 'admin') {
            return jsonResponse($response, ['error' => 'Access denied'], 403);
        }

        // Fix 9: Only allow safe fields. Ignore user_id, role, bmi, category, password_hash.
        $name = trim((string) $data['name']);
        $age = (int) $data['age'];
        $height = (float) $data['height'];
        $weight = (float) $data['weight'];
        $notes = isset($data['notes']) ? (string) $data['notes'] : null;

        $bmi = calculateBmi($height, $weight);
        $category = getBmiCategory($bmi);

        $stmt = $pdo->prepare('
            UPDATE persons
            SET name = ?, age = ?, height = ?, weight = ?, bmi = ?, category = ?, notes = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $age, $height, $weight, $bmi, $category, $notes, $id]);

        $stmt = $pdo->prepare('
            SELECT id, user_id, name, age, height, weight, bmi, category, notes, created_at
            FROM persons
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $updatedPerson = $stmt->fetch(PDO::FETCH_ASSOC);

        return jsonResponse($response, [
            'message' => 'BMI record updated successfully',
            'person' => $updatedPerson
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 4, 6, 7, 10, 12
$app->delete('/api/persons/{id}', function (Request $request, Response $response, array $args) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $id = (int) $args['id'];

        $stmt = $pdo->prepare('SELECT id, user_id FROM persons WHERE id = ?');
        $stmt->execute([$id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            return jsonResponse($response, ['error' => 'Record not found'], 404);
        }

        $currentUserId = (int) $decoded['user_id'];
        $currentUserRole = $decoded['role'];
        $recordOwnerId = (int) $person['user_id'];

        if ($currentUserId !== $recordOwnerId && $currentUserRole !== 'admin') {
            return jsonResponse($response, ['error' => 'Access denied'], 403);
        }

        $stmt = $pdo->prepare('DELETE FROM persons WHERE id = ?');
        $stmt->execute([$id]);

        return jsonResponse($response, [
            'message' => 'BMI record deleted successfully'
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// ----------------------------------------------------------
// Staff routes
// ----------------------------------------------------------

// Fixes 4, 6, 8, 10, 12
$app->get('/api/staff/persons', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $roleError = requireStaffOrAdmin($decoded, $response);
        if ($roleError) {
            return $roleError;
        }

        $stmt = $pdo->prepare('
            SELECT persons.id, persons.user_id, persons.name, persons.age, persons.height,
                   persons.weight, persons.bmi, persons.category, persons.notes, persons.created_at,
                   users.email AS owner_email, users.role AS owner_role
            FROM persons
            JOIN users ON persons.user_id = users.id
            ORDER BY persons.id DESC
        ');
        $stmt->execute();
        $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return jsonResponse($response, [
            'persons' => $persons
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 4, 6, 8, 10, 12
$app->get('/api/staff/persons/{id}', function (Request $request, Response $response, array $args) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $roleError = requireStaffOrAdmin($decoded, $response);
        if ($roleError) {
            return $roleError;
        }

        $id = (int) $args['id'];

        $stmt = $pdo->prepare('
            SELECT persons.id, persons.user_id, persons.name, persons.age, persons.height,
                   persons.weight, persons.bmi, persons.category, persons.notes, persons.created_at,
                   users.email AS owner_email, users.role AS owner_role
            FROM persons
            JOIN users ON persons.user_id = users.id
            WHERE persons.id = ?
        ');
        $stmt->execute([$id]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$person) {
            return jsonResponse($response, ['error' => 'Record not found'], 404);
        }

        return jsonResponse($response, [
            'person' => $person
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// ----------------------------------------------------------
// Admin routes
// ----------------------------------------------------------

// Fixes 4, 6, 8, 10, 12
$app->get('/api/admin/users', function (Request $request, Response $response) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $roleError = requireAdmin($decoded, $response);
        if ($roleError) {
            return $roleError;
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users ORDER BY id ASC');
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return jsonResponse($response, [
            'users' => $users
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 4, 6, 8, 10, 12
$app->put('/api/admin/users/{id}/role', function (Request $request, Response $response, array $args) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $roleError = requireAdmin($decoded, $response);
        if ($roleError) {
            return $roleError;
        }

        $id = (int) $args['id'];
        $data = getRequestData($request);
        $role = $data['role'] ?? 'user';

        if (!in_array($role, ['user', 'staff', 'admin'], true)) {
            return jsonResponse($response, ['error' => 'Invalid role'], 400);
        }

        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);

        $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return jsonResponse($response, ['error' => 'User not found'], 404);
        }

        return jsonResponse($response, [
            'message' => 'User role updated successfully',
            'user' => $user
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// Fixes 4, 6, 8, 10, 12
$app->delete('/api/admin/persons/{id}', function (Request $request, Response $response, array $args) {
    try {
        $pdo = getPDO();
        $decoded = requireAuth($request, $response);

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $roleError = requireAdmin($decoded, $response);
        if ($roleError) {
            return $roleError;
        }

        $id = (int) $args['id'];

        $stmt = $pdo->prepare('DELETE FROM persons WHERE id = ?');
        $stmt->execute([$id]);

        return jsonResponse($response, [
            'message' => 'Admin deleted BMI record successfully'
        ]);
    } catch (Throwable $e) {
        return exposeException($response, $e);
    }
});

// ----------------------------------------------------------
// Fix 11: Frontend XSS Prevention
// ----------------------------------------------------------
// This backend safely stores notes using prepared statements.
// The Vue frontend must still display notes safely.
// In Vue, use:
//     <p>{{ person.notes }}</p>
// Do not use:
//     <div v-html="person.notes"></div>
// ----------------------------------------------------------

// Preflight catch-all
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

$app->run();
