<?php

namespace qsun\ModelAnnotation;

use Log;
use ReflectionClass;
use DB;

class Annotation {
    public static function annotateTable($app, $table) {
        foreach (glob("{$app->path}/*.php") + glob("{$app->path}/**/*.php") as $file) {
            $filename = basename($file);
            $name = substr($filename, 0, strlen($filename)-4);
            $possible_class_name = studly_case(str_singular($table));

            if ($name == $possible_class_name) {
                Log::debug('File match with table: ' . $name);

                $description = self::getTableStructure($table);
                self::updateAnnotation($file, $description);
            }
        }
    }

    protected static function getTableStructure($table) {
        $description = DB::select('SHOW CREATE TABLE ' . $table)[0]->{'Create Table'};
        return $description;
    }

    protected static function updateAnnotation($file, $description) {
        $content = file_get_contents($file);
        $exists = (0 !== preg_match("/\/\* MODEL ANNOTATION:\n/s", $content));

        /* If does not exist, we add a placeholder */
        if (!$exists) {
            $content = preg_replace('/<\?(php)?/', "<?php\n/* MODEL ANNOTATION:\nEND MODEL ANNOTATION */", $content, 1);
        }

        $content = preg_replace("/MODEL ANNOTATION:.*?END MODEL ANNOTATION/s", "MODEL ANNOTATION:\n{$description}\nEND MODEL ANNOTATION", $content, 1);
        file_put_contents($file, $content);
    }
}