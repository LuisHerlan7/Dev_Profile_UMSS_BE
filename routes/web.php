use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

Route::post('/register', [RegisterController::class, 'register']);