<?php
require_once __DIR__ . '/../../config/Connection.php';

function getClientStatistics() {
    global $pdo;
    try {
        // Today's registrations
        $today = $pdo->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role = 'client' 
            AND DATE(date_created) = CURDATE()"
        )->fetch()['count'];

        // This week's registrations
        $week = $pdo->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role = 'client' 
            AND WEEK(date_created) = WEEK(CURDATE()) 
            AND YEAR(date_created) = YEAR(CURDATE())"
        )->fetch()['count'];

        // This month's registrations
        $month = $pdo->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role = 'client' 
            AND MONTH(date_created) = MONTH(CURDATE()) 
            AND YEAR(date_created) = YEAR(CURDATE())"
        )->fetch()['count'];

        // This year's registrations by month
        $yearly = $pdo->query("
            SELECT MONTH(date_created) as month, COUNT(*) as count 
            FROM users 
            WHERE role = 'client' 
            AND YEAR(date_created) = YEAR(CURDATE()) 
            GROUP BY MONTH(date_created) 
            ORDER BY month"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'stats' => [
                'today' => $today,
                'week' => $week,
                'month' => $month,
                'yearly' => $yearly
            ]
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}