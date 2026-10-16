<?php
// helpers.php —— 公共辅助函数
// 任何需要这些函数的页面，先 require_once 'helpers.php';

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return '—';
        $ts = strtotime($datetime);
        $diff = time() - $ts;

        if ($diff < 60)         return 'just now';
        if ($diff < 3600)       return floor($diff / 60) . 'm ago';
        if ($diff < 86400)      return floor($diff / 3600) . 'h ago';
        if ($diff < 86400 * 2)  return 'Yesterday';
        if ($diff < 86400 * 7)  return floor($diff / 86400) . 'd ago';
        return date('d M Y', $ts);
    }
}

if (!function_exists('formatClass')) {
    function formatClass($major, $gradeLevel) {
        $majorShort = 'SE';
        if (stripos($major, 'Cloud') !== false)           $majorShort = 'CC';
        elseif (stripos($major, 'Information') !== false) $majorShort = 'IT';
        elseif (stripos($major, 'Software') !== false)    $majorShort = 'SE';

        if ($gradeLevel >= 12) {
            $year = $gradeLevel - 12 + 1;
        } else {
            $year = $gradeLevel;
        }

        return $majorShort . '-' . $year . 'B';
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes) {
        if ($bytes < 1024)               return $bytes . ' B';
        if ($bytes < 1024 * 1024)        return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
        return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
    }
}

if (!function_exists('parseSizeToBytes')) {
    function parseSizeToBytes($size) {
        if (empty($size)) return 0;
        if (is_numeric($size)) return (int)$size;

        $size = strtoupper(trim($size));
        $num  = (float)$size;

        if (strpos($size, 'GB') !== false) return (int)($num * 1024 * 1024 * 1024);
        if (strpos($size, 'MB') !== false) return (int)($num * 1024 * 1024);
        if (strpos($size, 'KB') !== false) return (int)($num * 1024);
        if (strpos($size, 'B')  !== false) return (int)$num;
        return (int)$num;
    }
}
?>