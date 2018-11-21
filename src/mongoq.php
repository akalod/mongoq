<?php
/**
 * User    : Seyhan 'sTaRs' YILDIZ
 * Mail    : syhnyldz@gmail.com
 * Company : Digital Panzehir
 * Web     : www.digitalpanzehir.com
 * Date    : 11/2/2018
 * Time    : 10:25
 */

namespace akalod;

use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\BSON\Regex;

class mongoq
{
    private $client = null;
    public static $DSN = 'mongodb://demo.digitalpanzehir.com:27017';
    public static $db_name = 'arge';

    const DESC = -1;
    const ASC = 1;


    public static $Q;
    private $stack;
    private $joins = [];
    private $wheres = \stdClass::class;
    private $options = [];

    /**
     * @param $id
     * @return ObjectId
     */
    public static function ObjectID($id)
    {
        return new ObjectId($id);
    }

    public function limit($limit = 1)
    {
        $this->options['limit'] = $limit;
        return $this;
    }

    /**
     * select collection like table
     * @param $name
     * @return $this
     */
    public function collection($name)
    {
        $this->stack = $this->client->selectCollection($name);
        return $this;
    }

    /**
     * pipline'ı resetleme static kullanımlarda fetch edilen datadan sonra ayarların sıfırlanması gerekiyor
     */
    private function resetPipeLine()
    {
        $this->wheres = new \stdClass();
        $this->options = [];
        $this->joins = [];
    }

    /**
     * OneToOne ilişkiler için unwind işlemi
     * @param $key
     * @return $this
     */
    public function oneToOne($key)
    {
        $this->joins[] = ['$unwind' => ["path" => '$' . $key, "preserveNullAndEmptyArrays" => true]];
        return $this;
    }

    /**
     * finaly, checking class type  for disallow to cursor problems
     * @param $data
     * @return null
     */
    private function lastTypeCheck($data)
    {

        if (!is_array($data)) {
            return null;
        }
        return $data;
    }

    /**
     * @param string $method
     * @return mixed
     * @throws \Exception
     */
    private function prepare($method = 'findOne')
    {
        if ($this->joins || in_array('project', $this->options)) {
            if ($method == 'findOne' || $method == 'find' || $method = 'toJSON') {
                $r = $this->joins;
                $r[] = ['$match' => $this->wheres];

                foreach ($this->options as $k => $v) {
                    $r[] = ['$' . $k => $v];
                }

                $method == 'toJSON' ?
                    $r = json_encode($r) :
                    $r = $this->stack->aggregate($r);

                $this->resetPipeLine();
                return $this->lastTypeCheck($r->toArray());
            } else {
                throw new \Exception('You can not run this command with have been joined table');
            }
        }
        if (isset($this->options['project'])) {
            $this->options['projection'] = $this->options['project'];
            unset($this->options['project']);
        }

        $method == 'toJSON' ?
            $r = json_encode([$this->wheres, $this->options]) :
            $r = $this->stack->$method($this->wheres, $this->options);

        $this->resetPipeLine();
        return $this->lastTypeCheck($r->toArray());
    }

    /**
     * @param null $keys
     * @return array|\Exception|mixed
     */
    public function first($keys = null)
    {
        $this->setReturnColumns($keys);

        $this->limit(1);
        try {
            $r = $this->prepare('find');
        } catch (\Exception $e) {
            return $e;
        }

        if ($r) {
            foreach ($r as $i) {
                $r = $this->workStructure($i);
            }
        }

        return $r;
    }

    /**
     * @param $table
     * @return mixed
     */
    public function create($table)
    {
        return $this->client->createCollection($table);
    }

    /**
     * Drop your collection !!!
     * @param null $table
     * @return mixed
     */
    public function drop($table = null)
    {
        if ($table)
            return $this->collection($table)->drop();

        return $this->stack->drop();
    }

    /**
     * objectId has been changed by string for easy access
     * @param $data
     * @return mixed
     */
    private function setStructure(&$data)
    {
        if (isset($data) && $data['_id']) {
            $y = (array)$data['_id'];
            $data['_id'] = $y['oid'];
        }
        return $data;
    }

    /**
     * @param $data
     * @return array
     */
    private function workStructure($data)
    {
        $temp = [];
        if (is_array($data)) {
            foreach ($data as $i) {
                $this->setStructure($i);
                $temp[] = $i;
            };

        } else {
            return $this->setStructure($data);
        }

        return $temp;
    }

    private function setReturnColumns($keys = null)
    {

        if (isset($keys) && is_array($keys)) {
            $project = new \stdClass();
            foreach ($keys as $k => &$v) {
                if (!is_array($v)) {
                    $project->$v = 1;
                } else {
                    $project->$k = $v;
                }
            }
            $this->options['project'] = $project;
        }

    }

    /**
     * @param null $keys
     * @return mixed
     * @throws \Exception
     */
    public function toJSON($keys = null)
    {
        $this->setReturnColumns($keys);
        return $this->prepare('toJSON');
    }

    /**
     * @param null $keys // select return keys
     * @return array|\Exception
     */
    public function get($keys = null)
    {
        $this->setReturnColumns($keys);

        $data = [];
        try {
            $r = $this->prepare('find');
        } catch (\Exception $e) {
            return $e;
        }

        var_dump($r);

        echo "YY";
        if ($r) {
            foreach ($r as $i) {
                $data[] = $this->workStructure($i);
            }
        }

        return $data;

    }

    /**
     * @param null $dsn
     * @param null $db
     * @return mongoq
     */
    public static function init($dsn = null, $db = null)
    {
        self::$Q = new self();
        self::$Q->wheres = new \stdClass();
        if ($dsn)
            self::$DSN = $dsn;
        $client = new Client(self::$DSN);
        if ($db)
            self::$db_name = $db;
        self::$Q->client = $client->selectDatabase(self::$db_name);

        return self::$Q;
    }

    public function distinct($column_name)
    {
        return $this->stack->distinct($column_name);
    }

    /**
     * @param $table
     * @param $first (column name on joined table)
     * @param $second (column name on joiner table)
     * @param null $alias
     * @return $this
     */
    public function leftJoin($table, $first, $second, $alias = null)
    {
        $this->joins[] = ['$lookup' => [
            'from' => $table,
            'localField' => $first,
            'foreignField' => $second,
            'as' => $alias ? $alias : 'join_' . count($this->joins)
        ]];

        return $this;
    }

    /**
     * case-insensitive flag is i
     * @param $data -> can be array like as ["_id"=>self::ObjectID("5bdc01ef45bfc30ea8004d92")]
     * @param null $value
     * @param bool $like -> that is ` where like '%key%' ` syntax on SQL
     * @return $this
     */
    public function where($data, $value = null, $like = false)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $this->wheres->$k = $v;
            }
        } else {
            $this->wheres->$data = is_numeric($value) ? intval($value) : new Regex($like ? $value : "^$value\$", "i");
        }
        return $this;
    }

    /**
     * @param $key
     * @param $val
     * @return mongoq
     */
    public function whereLike($key, $val)
    {
        return $this->where($key, $val, true);
    }

    /**
     * @param $key
     * @return mongoq
     */
    public function whereNotNull($key)
    {
        return $this->where([$key => ['$exists' => true, '$ne' => null]]);
    }

    public function unwind($key)
    {
        $this->options['unwind'] = '$' . $key;
    }

    /**
     * limited to unset or null variable key index
     * @param $key
     * @return mongoq
     */
    public function whereIsNull($key)
    {
        return $this->where(
            ['$or' => [
                [$key => ['$exists' => false]],
                [$key => null]
            ]]
        );
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function deleteAll()
    {
        try {
            return $this->prepare('deleteAll');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function delete()
    {
        try {
            return $this->prepare('deleteOne');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $data
     */
    public function insert($data)
    {
        if ($this->stack) {
            $this->stack->insertOne($data);
        }
    }

    /**
     * @param $data
     * @return bool
     */
    public function updateOrCreate($data)
    {
        return $this->update($data, true, true);
    }

    /**
     * @param $data
     * @param bool $force
     * @return bool
     */
    public function updateAll($data, $force = true)
    {
        return $this->update($data, $force, false, true);
    }

    /**
     * @param $key
     * @param int $sort
     * @return $this
     */
    public function orderBy($key, $sort = self::DESC)
    {
        $this->options['sort'] = [$key => $sort];
        return $this;

    }

    /**
     * @param array $data
     * @param bool $force -> this option replaces your data with whatever you send. If you select "false", Doesn't overwrite except for your previously assigned keys
     * @param bool $createIfDoesNotExist
     * @param bool $effectiveMultiDocument
     * @return bool
     */
    public function update(array $data, $force = false, $createIfDoesNotExist = false, $effectiveMultiDocument = false)
    {
        if ($this->stack) {

            $this->options['upsert'] = $createIfDoesNotExist;
            $this->options['multi'] = $effectiveMultiDocument;

            if ($force) {
                return $this->stack->update($this->wheres, $data, $this->options);
            } else {
                $effectiveData = $this->get();
                foreach ($effectiveData as $item) {
                    $item->_id = self::ObjectID($item->_id);
                    foreach ($data as $k => $v) {
                        $item[$k] = $v;
                    }
                    $this->stack->replaceOne(['_id' => self::ObjectID($item->_id)], $item);
                }
                return true;
            }
        }

    }

}
