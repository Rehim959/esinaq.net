<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ExamController;
use App\Controllers\HomeController;
use App\Controllers\ParentController;
use App\Core\Router;

$router = new Router();

// Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/dil/{locale}', [HomeController::class, 'setLanguage']);

// Parent auth
$router->get('/qeydiyyat', [AuthController::class, 'showRegister']);
$router->post('/qeydiyyat', [AuthController::class, 'register']);
$router->get('/valideyn/giris', [AuthController::class, 'showLogin']);
$router->post('/valideyn/giris', [AuthController::class, 'login']);
$router->post('/cixis', [AuthController::class, 'logout']);
$router->get('/sifremi-unutdum', [AuthController::class, 'showForgot']);
$router->post('/sifremi-unutdum', [AuthController::class, 'forgot']);
$router->get('/sifre-berpa', [AuthController::class, 'showReset']);
$router->post('/sifre-berpa', [AuthController::class, 'reset']);

// Parent panel
$router->get('/valideyn', [ParentController::class, 'dashboard']);
$router->get('/valideyn/usaq-elave', [ParentController::class, 'showAddChild']);
$router->post('/valideyn/usaq-elave', [ParentController::class, 'addChild']);
$router->get('/valideyn/usaq/{id}', [ParentController::class, 'childResults']);
$router->get('/valideyn/netice/{id}', [ParentController::class, 'sessionDetail']);

// Child exam
$router->get('/imtahan/{token}', [ExamController::class, 'entry']);
$router->post('/imtahan/{token}', [ExamController::class, 'login']);
$router->get('/imtahan/{token}/siyahi', [ExamController::class, 'listExams']);
$router->post('/imtahan/{token}/basla/{examId}', [ExamController::class, 'start']);
$router->get('/imtahan/{token}/kec/{sessionId}', [ExamController::class, 'take']);
$router->post('/imtahan/{token}/cavab/{sessionId}', [ExamController::class, 'answer']);
$router->post('/imtahan/{token}/teslim/{sessionId}', [ExamController::class, 'submit']);
$router->get('/imtahan/{token}/netice/{sessionId}', [ExamController::class, 'result']);

// Admin
$router->get('/admin/login', [AdminController::class, 'showLogin']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->post('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/suallar', [AdminController::class, 'questions']);
$router->get('/admin/suallar/elave', [AdminController::class, 'showImport']);
$router->post('/admin/suallar/elave', [AdminController::class, 'import']);
$router->post('/admin/suallar/sil/{id}', [AdminController::class, 'deleteQuestion']);
$router->get('/admin/imtahanlar', [AdminController::class, 'exams']);
$router->get('/admin/imtahanlar/yeni', [AdminController::class, 'showCreateExam']);
$router->post('/admin/imtahanlar/yeni', [AdminController::class, 'createExam']);
$router->post('/admin/imtahanlar/baslat/{id}', [AdminController::class, 'startExam']);
$router->post('/admin/imtahanlar/bitir/{id}', [AdminController::class, 'stopExam']);
$router->get('/admin/imtahanlar/monitor/{id}', [AdminController::class, 'examMonitor']);
$router->get('/admin/valideynler', [AdminController::class, 'parents']);
$router->get('/admin/valideyn/{id}', [AdminController::class, 'parentShow']);
$router->post('/admin/valideyn/sifre/{id}', [AdminController::class, 'resetParentPassword']);
$router->post('/admin/valideyn/sil/{id}', [AdminController::class, 'deleteParent']);
$router->get('/admin/usaqlar', [AdminController::class, 'children']);
$router->post('/admin/usaq/sifre/{id}', [AdminController::class, 'resetChildPassword']);
$router->post('/admin/usaq/sil/{id}', [AdminController::class, 'deleteChild']);

return $router;
