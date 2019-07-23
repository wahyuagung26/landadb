# Landa DB

Simple Mysql PDO CRUD library

## Installation

Install with [Composer](http://getcomposer.org/)

Add `cahkampung/landa-db` to require in composer.json

`"require": { "cahkampung/landa-db": "^1.2" },`

Run `composer install`

## How To Use

### Connection

```
$config = [
  'DB_DRIVER'      => 'mysql',
  'DB_HOST'        => 'localhost',
  'DB_USER'        => 'root',
  'DB_PASS'        => 'password',
  'DB_NAME'        => 'database',
  'CREATED_USER'   => 'created_by',
  'CREATED_TIME'   => 'created_at',
  'CREATED_TYPE'   => 'int',
  'MODIFIED_USER'  => 'modified_by',
  'MODIFIED_TIME'  => 'modified_at',
  'MODIFIED_TYPE'  => 'int',
  'DISPLAY_ERRORS' => false,
  'USER_ID'        => $_SESSION['user']['id'],
];

$db = new Cahkampung\Landadb($config);
```

### Insert

`$db->insert(TABLE_NAME, DATA);`

Example : 
```
$data = [
  'name' => 'john',
  'email' => 'john@example.com'
];

$db->insert('user_table', $data);
```

### Update

`$db->update(TABLE_NAME, DATA, PARAMS);`

Example : 
```
$data = [
  'name' => 'john',
  'email' => 'john@example.com'
];

$db->update('user_table', $data, ['id' => 1]);
```
### Delete

`$db->delete(TABLE_NAME, PARAMS);`

Example :
```
$db->delete('user_table', ['id' => 1]);
```

### Select ###

#### select() ####

`select(FIELDS)`

**FIELDS** can be array format, default value is `*`

#### from() ####

`from(TABLE)`

#### where() ####

`where(FIELD_NAME, FILTER, VALUE)`

#### andWhere() ####

`andWhere(FIELD_NAME, FILTER, VALUE)`

#### orWhere() ####

`andWhere(FIELD_NAME, FILTER, VALUE)`

#### customWhere() ####

`customWhere(WHERE_STRING, FILTER)`

Default filter is `And` 

Example : 

`customWhere('name = "john" or nationallity = "indonesia"', 'AND');`

Will generate `AND (name="john" or nationallity="indonesia");`

#### join() ####

`join(JOIN TYPE, TABLE, ONCLAUSE)`

#### leftJoin() ####

`leftJoin(TABLE, ONCLAUSE)`

#### rightJoin() ####

`rightJoin(TABLE, ONCLAUSE)`

#### innerJoin() ####

`innerJoin(TABLE, ONCLAUSE)`

#### limit() ####

`limit(INT)`

#### offset() ####

`offset(INT)`

#### orderBy() ####

`orderBy(FIELD)`

#### groupBy ####

`groupBy(FIELD)`

#### findAll() ####

Fetch all result from query

Example :
```
$db->findAll('select * from user_table where name like "%john%" order by name ASC limit 10 offset 0');
```
Or
```
$db->select()
    ->from('user_table')
    ->where('name','LIKE','john')
    ->limit(10)
    ->offset(0)
    ->orderBy('name ASC')
$getUsers = $db->findAll();
```

#### find() ####

Fetch 1 results from query

Example :
```
$db->find('select * from user_table where name like "%john%" order by name ASC');
```
Or 
```
$db = new Cahkampung\Landadb;
$db->select()
    ->from('user_table')
    ->where('name','LIKE','john')
    ->orderBy('name ASC')
$getUsers = $db->find();
```

