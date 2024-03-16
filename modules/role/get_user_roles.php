<?php

class UserRolesManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserRolesByPage() {
        // Check if page and limit parameters are provided
        if (isset($_GET['page']) && isset($_GET['limit'])) {
            $limit = $_GET['limit']; // Number of records per page
            if ($limit > 100) {
                echo json_encode(array("message" => "Exceed the allowed limit (<=100)"));
                exit;
            }

            // Prepare the main SQL query to fetch user roles
            $sql = "SELECT user_login.id as user_id, user_login.username, roles.role, roles.name_role 
                    FROM user_login 
                    LEFT JOIN user_role ON user_login.id = user_role.user_id 
                    LEFT JOIN roles ON roles.id = user_role.role_id";

            // Execute the query to get total rows
            $totalStmt = $this->pdo->query($sql);
            $totalResult = $totalStmt->fetchAll(PDO::FETCH_ASSOC);
            $total_rows = count($totalResult);

            // Calculate total pages
            $total_pages = ceil($total_rows / $limit);

            // Check for valid page
            if (isset($_GET['page']) && $_GET['page'] > $total_pages) {
                echo json_encode(array("message" => "Invalid page number"));
                exit;
            }

            // Get start index for pagination
            $page = isset($_GET['page']) ? $_GET['page'] : 1;
            $start = ($page - 1) * $limit;

            // Prepare the SQL query with LIMIT for pagination
            $stmt = $this->pdo->prepare("SELECT user_login.id as user_id, user_login.username, roles.role, roles.name_role 
                                         FROM user_login 
                                         LEFT JOIN user_role ON user_login.id = user_role.user_id 
                                         LEFT JOIN roles ON roles.id = user_role.role_id 
                                         LIMIT :start, :limit");
            $stmt->bindParam(':start', $start, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organize the data into desired format
            $arrAuthPermit = [];
            foreach ($userRoles as $index => $value) {
                if (!is_null($value["role"])) {
                    $arrAuthPermit[$value["username"]][] = implode("", explode(",", $value["role"]));
                } else {
                    $arrAuthPermit[$value["username"]] = [];
                }
            }

            // Return the result as JSON
            echo json_encode([
                'page' => $page,
                'total_pages' => $total_pages,
                "total_record" => $total_rows,
                "data" => $arrAuthPermit
            ]);
        } else {
            echo json_encode(array("message" => "Please provide both page and limit"));
        }
    }
}

// Usage:
// $pdo = new PDO("mysql:host=localhost;dbname=mydatabase", "username", "password");
// $userRoleManager = new UserRoleManager($pdo);
// $userRoleManager->getUserRolesByPage();

?>
