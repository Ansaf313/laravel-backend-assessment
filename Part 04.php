<?php

//Install Laravel & Passport

//If you haven’t installed Laravel yet, run:

composer create-project --prefer-dist laravel/laravel eav_project
cd eav_project
composer require laravel/passport
php artisan migrate
php artisan passport:install

Add Laravel\Passport\HasApiTokens to the User model.



//Database Migrations

//Create attributes Table
php artisan make:migration create_attributes_table
//Modify the migration file:

public function up()
{
    Schema::create('attributes', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->enum('type', ['text', 'number', 'date', 'select']);
        $table->timestamps();
    });
}

//Create attribute_values Table

php artisan make:migration create_attribute_values_table

//Modify the migration:

public function up()
{
    Schema::create('attribute_values', function (Blueprint $table) {
        $table->id();
        $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
        $table->foreignId('entity_id'); // Refers to Project ID
        $table->string('value');
        $table->timestamps();
    });
}

//Create projects Table

php artisan make:migration create_projects_table

//Modify the migration:

public function up()
{
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
}

//Run migrations:

php artisan migrate



//Models

Project Model

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function attributes()
    {
        return $this->hasMany(AttributeValue::class, 'entity_id');
    }
}


//Attribute Model

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'type'];
}


//AttributeValue Model

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    use HasFactory;
    protected $fillable = ['attribute_id', 'entity_id', 'value'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}



//Step 4: API Controllers

//ProjectController

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\AttributeValue;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        $project = Project::create($request->only('name'));

        if ($request->has('attributes')) {
            foreach ($request->attributes as $attr_id => $value) {
                AttributeValue::create([
                    'attribute_id' => $attr_id,
                    'entity_id' => $project->id,
                    'value' => $value,
                ]);
            }
        }
        return response()->json($project->load('attributes'), 201);
    }

    public function index(Request $request)
    {
        $query = Project::with('attributes');
        
        if ($request->has('filters')) {
            foreach ($request->filters as $attribute => $value) {
                $query->whereHas('attributes', function ($q) use ($attribute, $value) {
                    $q->where('attribute_id', $attribute)->where('value', $value);
                });
            }
        }
        return response()->json($query->get());
    }
}



//Routes

//Modify routes/api.php:

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::resource('projects', ProjectController::class);
});



//Authentication (Laravel Passport)

//AuthController

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\HasApiTokens;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $token = $user->createToken('API Token')->accessToken;
        return response()->json(['token' => $token]);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        
        $token = Auth::user()->createToken('API Token')->accessToken;
        return response()->json(['token' => $token]);
    }
}



//Example API Requests

//Register a User

curl -X POST http://localhost:8000/api/register -d "name=John Doe&email=john@example.com&password=123456"


//Login

curl -X POST http://localhost:8000/api/login -d "email=john@example.com&password=123456"


//Response:

{ "token": "your_access_token" }



//Create a Project with Attributes

curl -X POST http://localhost:8000/api/projects \
     -H "Authorization: Bearer your_access_token" \
     -d "name=ProjectX&attributes[1]=IT&attributes[2]=2024-02-13"



//Fetch All Projects

curl -X GET http://localhost:8000/api/projects -H "Authorization: Bearer your_access_token"


//Filter Projects

curl -X GET "http://localhost:8000/api/projects?filters[1]=IT" -H "Authorization: Bearer your_access_token"
