<?php

namespace sebouchu\ModelAnnotation;

use Log;
use ReflectionClass;
use DB;
use Illuminate\Support\Str;

class Annotation {
    public static function annotateTable($app, $table) {
        foreach (glob("{$app->path}/*.php") + glob("{$app->path}/**/*.php") as $file) {
            $filename = basename($file);
            $name = substr($filename, 0, strlen($filename)-4);
            $possible_class_name = Str::studly(Str::singular($table));

            if ($name == $possible_class_name) {
                Log::debug('File match with table: ' . $name);

                $description = self::getTableStructure($table);
                self::updateAnnotation($file, $description);
            }
        }
    }

    protected static function getTableStructure($table) {
        $description = '';
        $fields = DB::select('DESC ' . $table);

        foreach ($fields as $field) {
            $type = $field->{'Type'};
            if (1==preg_match('/^int/', $type)) {
                $type = 'int';
            } elseif ($type == 'timestamp') {
                $type = 'int';
            } elseif (1==preg_match('/^varchar/', $type)) {
                $type = 'string';
            } else {
                // pass
            }

            if ($field->{'Null'} === 'YES') {
                $type = "$type|null";
            }

            $default = $field->{'Default'};
            if ('' == $default) {
                $default = 'null';
            }

            $key = $field->{'Key'};
            if ('' == $key) {
                $key = 'nil';
            }

            $description = $description . "@property\t" . str_pad("\$".$field->{'Field'}, 25)
                . "Type: ". str_pad($field->{'Type'}, 25) ."Extra: ". str_pad($field->{'Extra'}, 20)
                . "Default: ". str_pad($default, 10) ."Key: $key\n";
        }

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