INSERT INTO `user`
(name, email_address, field_x, ...)
VALUES
(:name, :email, :field_x, ...)

// a base class for operating against collection of objects adhering to the model
abstract class PdoModelFactory
{
// a property to store PDO object to be used globally for the factory
protected static $pdo;

// a property to store name of class that this factory should instantiate
protected static $class;

// a static function to inject PDO object to factory
public static function setPDO(PDO $pdo) {
self::$pdo = $pdo;
}

// a function that can be used within class to determine if factory has
// been properly initialized. This can be overridden by inheriting classes.
protected static function validateFactoryState() {
if(self::$pdo instanceof PDO === false) {
throw new Exception('Factory does not have PDO initialized');
}
$class = self::$class;
if(empty($class::getTable())) {
throw new Exception(
'Class specified in factory does not have DB table defined.'
);
}
if(empty($class::getPrimaryKeyField())) {
throw new Exception(
'Class specified in factory does not have primary key field defined.'
);
}
}

// method to validate id value. Again overridable as needed.
protected static function validateId($id) {
$class = self::$class;
// have class you are working with validate the input data
try {
$class::validateId($id);
} catch Exception ($e) {
// perhaps log an rethrow or wrap underlying exception
}
}

protected static function validateFieldData(Array $data) {
$class = self::$class;
// have class you are working with validate the input data
try {
$result = $class::validateFieldData($data);
} catch Exception ($e) {
// perhaps log and rethrow or wrap underlying exception
}
}

protected static function validateFieldExists($field) {
$class = self::$class;
// have class you are working with validate the input data
try {
$result = $class::validateFieldExists($field);
} catch Exception ($e) {
// perhaps log and rethrow or wrap underlying exception
}
}

// maybe provide some overridable functions for simplest of operations
public static function getById($id) {
self::validateFactoryState();
self::validateId($id);

// try to instantiate a new item of the class
// passing PDO object to class as dependency
try {
$obj = new self::$class($id, self::$pdo);
} catch Exception($e) {
// perhaps log error and rethrow or recover
$obj = null;
}

return $obj;
}

public static function getAll(PDO $pdo) {
self::validateFactoryState();

$collection = [];

$sql = "SELECT `{self::$primaryKeyField}` FROM `{self::$table}`";

// not shown - get list of id's and build array of objects in $collection

return $collection;
}

public static function getAllPaginated($offset, $limit, $sortField, $sortOrder) {
self::validateFactoryState();
self::validateFieldExists($sortField);

$collection = [];

// not shown build pagination query and build collection of objects

return $collection;
}


public static function create(Array $data) {
self::validateFactoryState();
self::validateFieldData($data);

// try to create a new instance of class
try {
$obj = new self::$class($data, self:$pdo);
} catch Exception ($e) {
// some handling here
}
return $obj;
}

public static function updateById($id, Array $data) {
self::validateFactoryState();
self::validateId($id);
self::validateFieldData($data);

// get instance of class from DB and update it.
try {
$obj = new self::$class($id, self::PDO);
$obj->update($data);
} catch Exception ($e) {
// some handling
}
// perhaps return updated object
return $obj;
}

// you might consider overriding this behavior to, for example,
// rather than actually delete database record, instead update it to flag
// as "deleted".
public static function deleteById($id) {
self::validateFactoryState();
self::validateId($id);

$sql = "DELETE FROM `{self::$table}`
WHERE `{self::$primaryKeyField}` = :id";

// not shown perform "delete" query returning true/false to caller
// to indicate success
}

// perhaps some abstract functions for operations against the collection
// which really need class-specific knowledge
abstract public static function findByValue($field, $value);
...
}

class UserFactory inherits PdoModelFactory
{
protected static $class = 'User';

// implement abstract methods
public static function findByValue($field, $value) {
self::validateFactoryState();

$collection = [];

// not shown - class-specific logic for allowing for field value searches

return $collection;
}
}

class Model implements JsonSerializable {
// static properties to indicate behavior common across all class instances
protected static $table;
// properties to store primary key field name, or field configurations
protected static $primaryKeyField = 'id';
protected static $fieldConfig = [];

// perhaps properties to store relationships to other models
protected static $hasMany;
protected static $belongsTo;

// instance properties
// these could represent fields you would expect on all models
protected $id;
protected $createdAt;
protected $updatedAt;

// this SHOULD be overridden in most inheriting classes to add additional logic
// to hydrate the instance
public function __construct($id, PDO $pdo) {
self:validateId($id);

$this->pdo = $pdo;
}

// common instance methods
public function getId() {
return $this->id;
}
// not shown - other common getters/setters as needed

// overridable method to implement JsonSerializable
// we just populate a StdClass object to be serialized
// with only properties we want to expose.
public function jsonSerialize() {
$obj = new StdClass();
$obj->id = $this->id;
$obj->createdAt = $this->createdAt;
$obj->updatedAt = $this->updatedAt;
return $obj;
}


// abstract instance methods, where logic must live in inheriting classes
// to execute desired behavior
public abstract function update($data);
public abstract function delete();

// static methods
// getter for table name
public static function getTable() {
return self::$table;
}

// method to validate id value. Overridable as needed.
protected static function validateId($id) {
$id = filter_var($id, FILTER_VALIDATE_INT)
if($id === false || $id < 1) {
throw new InvalidArgumentException(
'Invalid Id value passed. Positive integer value expected.'
);
}
}

// abstract static methods
// could be concrete methods if you find commonalities in your class logic
protected abstract static function validateFieldData($data);
protected abstract static function validateFieldExists($field);
}

class User extends Model {
protected static $table = 'user';

// here is where you would define fields for this concrete class
protected static $fieldConfig = [
...
];

// perhaps relate to other classes by class name
protected static $hasMany = ['Group', 'EmailAddress'];
protected static $belongsTo = ['Group'];

// instance properties
protected $name;
...

public function __construct($id, $pdo) {
parent::__construct($id, $pdo);

// class specific logic which would include querying DB for record at $id
// hydrating the class properties, etc.
}

// overriden jsonSerialize method
public function jsonSerialize() {
// start with parent property serialization
$obj = parent::jsonSerialize();
// add serializations specific to class
$obj->name = $this->name;
...
return $obj;
}

// implement abstract instance methods
public function update(Array $data) {
self::validateFieldData($data);

// not shown some logic to update values in DB and locally upon success
// perhaps update relationships if they have changed.
// perhaps return success/failure flag
}

public function delete() {
// perhaps perform DB deletion or deletion flag SQL
// and then render the object unusable an capable of throwing exceptions
// if ever accessed after this call.
}

// implement static abstract methods
protected static function validateFieldData($data) {
// not shown
}

protected static function validateFieldExists($field) {
// not shown
}
}

// Usage

// assume you have instantiated PDO connection into $pdo
// this could be in config file for example.
$pdo = new PDO(...);
UserFactory::setPdo($pdo);

// now later in code
// maybe user data has been input to create a new user record
$userData = ...;
$user = UserFactory::create($userData);
$user->update(['name' => 'new value');

// find by id
$idToFind = ...;
$user = UserFactory::getById($idToFind);

// serialize to JSON
$user_json = json_encode($user)
