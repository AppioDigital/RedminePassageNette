<?php
declare(strict_types=1);

namespace Appio\RedmineNette\Utils;

use Nette\StaticClass;

/**
 * @author Jakub Janata <jakubjanata@gmail.com>
 */
class Helpers
{
    use StaticClass;

    /**
     * @param string $date
     * @return string
     */
    public static function convertDateStringToRedmineFormat(string $date): string
    {
        return $date ? (new \DateTime($date))->format('Y-m-d') : $date;
    }

    /**
     * @param string $filename
     * @return bool
     */
    public static function isImage(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'], true);
    }
}
