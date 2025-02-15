<?php

//1. Attribute Model (Attribute.php)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type'];

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}




//2. AttributeValue Model (AttributeValue.php)

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

    public function project()
    {
        return $this->belongsTo(Project::class, 'entity_id');
    }
}



//3. Update Project Model (Project.php)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function attributes()
    {
        return $this->hasMany(AttributeValue::class, 'entity_id');
    }
}



//4. Migrations for Attribute and AttributeValue Tables

//Migration for Attributes Table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // text, date, number, select
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attributes');
    }
};

//Migration for AttributeValues Table

return new class extends Migration {
    public function up()
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->foreignId('entity_id')->constrained('projects')->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attribute_values');
    }
};



//5. API Routes (api.php)

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

Route::post('attributes', [ProjectController::class, 'createAttribute']);
Route::post('projects/{projectId}/attributes', [ProjectController::class, 'setAttributeValue']);
Route::get('projects', [ProjectController::class, 'getProjects']);
Route::get('projects/filter', [ProjectController::class, 'filterProjects']);




//6. API Controller (ProjectController.php)

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Attribute;
use App\Models\AttributeValue;

class ProjectController extends Controller
{
    public function createAttribute(Request $request)
    {
        $attribute = Attribute::create($request->all());
        return response()->json($attribute);
    }

    public function setAttributeValue($projectId, Request $request)
    {
        $request->merge(['entity_id' => $projectId]);
        $attributeValue = AttributeValue::create($request->all());
        return response()->json($attributeValue);
    }

    public function getProjects()
    {
        $projects = Project::with('attributes.attribute')->get();
        return response()->json($projects);
    }

    public function filterProjects(Request $request)
    {
        $projects = Project::whereHas('attributes', function ($query) use ($request) {
            $query->whereHas('attribute', function ($q) use ($request) {
                $q->where('name', $request->attributeName);
            })->where('value', $request->value);
        })->get();

        return response()->json($projects);
    }
}
