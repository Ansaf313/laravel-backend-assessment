<?php

//Step 1: Setup Models

//Run the following Artisan commands:

//php artisan make:model User -m
//php artisan make:model Project -m
//php artisan make:model Timesheet -m

//User Model (User.php)

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['first_name', 'last_name', 'email', 'password'];
}



//Project Model (Project.php)

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];
}



//Timesheet Model (Timesheet.php)

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = ['task_name', 'date', 'hours', 'user_id', 'project_id'];
}



//Step 2: Create Authentication API

//Run:

//php artisan make:controller AuthController

//AuthController.php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $request->user()->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token], 200);
    }
}



//Step 3: Create API Routes

//In routes/api.php:

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('projects', ProjectController::class);
});