# mongoq
MongoDB Query Builder (PHP)

# install
```php
composer require akalod/mongoq
```
# notices
- Case Insensetive "All where condition" (exist ObjectId)
- Auto Integer implement 
- Auto match DocumentId for update method
- Values of unsent keys can be kept for update methods

# using examples with alias
```php
Use akalod/mongoq as DB;

// static using example
DB::init("mongodb://localhost","db_name");
DB::$Q->collection('tasks')->get();

// construct example of multi line connections
$x = DB::init("mongodb://localhost","a_db");
$r = DB::init("mongodb://username:password@remote.server.com","b_db");

$r->collection('tasks')->get();
// or 
$x->collection('a_table')
  ->leftJoin('b_table','user_id',$r::ObjectID('_id'))
  ->where('user_id',1)
  ->get();
  
// you can use multiple where condition 
... ->where('status',1)->where('owner_id',2) 
//or 
.. ->where(['status'=>1,'owner_id'=>2])

// deleting examples
$r->collection('tasks')
  ->whereIsNull('owner_id')
  ->deleteAll(); // or delete() for removing one result.

// getting first data
.. ->first();
// or you can use limit
.. ->limit(1)->get();

// inserting Data
$r->collection('tasks')->insert(Array());

// if update else create (:
$r->collection('tasks')->updateOrCreate(Array()) // you can use this method with where condition

// data updating...
$r->collection('task')->whereIsNull('owner_id')->update(['owner_id'=>1,'email'=>'seyhan@d*.com'])
/* 
--result like this before using
{
    "_id" : ObjectId("5bea74ccdfce107bd4d781fb"),
    "address" : "blaaa blaaaaa",
    "email" : "syhnyldz@*.com"
}
-- after using.
{
    "_id" : ObjectId("5bea74ccdfce107bd4d781fb"),
    "address" : "blaaa blaaaaa",
    "email" : "seyhan@d*.com",
    "owner_id":1
}
-- if you use the force your result shoud like this
{
    "_id" : ObjectId("5bea74ccdfce107bd4d781fb"), 
    "email" : "seyhan@d*.com",
    "owner_id":1
}
*/

// you can list null and non exist keys
.. ->whereIsNull('name')->get(); //or whatever you want (delete/update)
// or you can list non Null and exist keys
.. ->whereNotNull('name')->get(); //whatever
// whereLike method is similar that  "where like '%key%'" on SQL
.. ->whereLike('key','val')->get(); //bla bla

//create collection (table)
$r->create('tableName');

//drop collection
$r->collection('tableName)->drop();
//or
$r->drop('tableName');

//distinct
$r->collection('collecionName')->distinct('keyName');


```
