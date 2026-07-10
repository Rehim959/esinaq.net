<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ExamController;
use App\Controllers\HomeController;
use App\Controllers\InviteController;
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
$router->post('/valideyn/usaq/mail/{id}', [ParentController::class, 'resendChildMail']);
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

// Parent exam invite confirmation (token link from email)
$router->get('/imtahan-dewet/{token}', [InviteController::class, 'confirm']);

// Admin
$router->get('/admin/login', [AdminController::class, 'showLogin']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->post('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/suallar', [AdminController::class, 'questions']);
$router->get('/admin/suallar/export', [AdminController::class, 'exportQuestions']);
$router->get('/admin/suallar/yeni', [AdminController::class, 'showCreateQuestion']);
$router->post('/admin/suallar/yeni', [AdminController::class, 'storeQuestion']);
$router->get('/admin/suallar/sekilli', [AdminController::class, 'showCreateImageQuestion']);
$router->post('/admin/suallar/sekilli', [AdminController::class, 'storeImageQuestion']);
$router->get('/admin/suallar/sekilli/duzelis/{id}', [AdminController::class, 'showEditImageQuestion']);
$router->post('/admin/suallar/sekilli/duzelis/{id}', [AdminController::class, 'updateImageQuestion']);
$router->post('/admin/suallar/media', [AdminController::class, 'uploadQuestionMedia']);
$router->get('/admin/suallar/elave', [AdminController::class, 'showImport']);
$router->post('/admin/suallar/elave', [AdminController::class, 'import']);
$router->get('/admin/suallar/elave/preview', [AdminController::class, 'importPreview']);
$router->post('/admin/suallar/elave/tesdiq', [AdminController::class, 'confirmImport']);
$router->post('/admin/suallar/elave/geri', [AdminController::class, 'cancelImportPreview']);
$router->get('/admin/suallar/bax/{id}', [AdminController::class, 'showQuestion']);
$router->post('/admin/suallar/yer/{id}', [AdminController::class, 'moveQuestion']);
$router->get('/admin/suallar/duzelis/{id}', [AdminController::class, 'showEditQuestion']);
$router->post('/admin/suallar/duzelis/{id}', [AdminController::class, 'updateQuestion']);
$router->post('/admin/suallar/sil/{id}', [AdminController::class, 'deleteQuestion']);
$router->post('/admin/suallar/sil-secilen', [AdminController::class, 'deleteQuestionsBulk']);
$router->post('/admin/suallar/sil-sorgu/{id}', [AdminController::class, 'requestQuestionDelete']);
$router->get('/admin/imtahanlar', [AdminController::class, 'exams']);
$router->get('/admin/imtahanlar/yeni', [AdminController::class, 'showCreateExam']);
$router->post('/admin/imtahanlar/yeni', [AdminController::class, 'createExam']);
$router->get('/admin/imtahanlar/duzelis/{id}', [AdminController::class, 'showEditExam']);
$router->post('/admin/imtahanlar/duzelis/{id}', [AdminController::class, 'updateExamSchedule']);
$router->post('/admin/imtahanlar/baslat/{id}', [AdminController::class, 'startExam']);
$router->post('/admin/imtahanlar/bitir/{id}', [AdminController::class, 'stopExam']);
$router->post('/admin/imtahanlar/yeniden/{id}', [AdminController::class, 'cloneExam']);
$router->post('/admin/imtahanlar/sil/{id}', [AdminController::class, 'deleteExam']);
$router->get('/admin/imtahanlar/monitor/{id}', [AdminController::class, 'examMonitor']);
$router->get('/admin/imtahanlar/dewetler/{id}', [AdminController::class, 'examInvites']);
$router->post('/admin/imtahanlar/dewet/tesdiq/{id}', [AdminController::class, 'approveInvite']);
$router->post('/admin/imtahanlar/dewet/ref/{id}', [AdminController::class, 'rejectInvite']);
$router->post('/admin/imtahanlar/dewet/yeniden/{id}', [AdminController::class, 'resendInvite']);
$router->get('/admin/valideynler', [AdminController::class, 'parents']);
$router->get('/admin/valideyn/{id}', [AdminController::class, 'parentShow']);
$router->post('/admin/valideyn/ad/{id}', [AdminController::class, 'updateParentName']);
$router->post('/admin/valideyn/sifre/{id}', [AdminController::class, 'resetParentPassword']);
$router->post('/admin/valideyn/sil/{id}', [AdminController::class, 'deleteParent']);
$router->get('/admin/usaqlar', [AdminController::class, 'children']);
$router->get('/admin/usaq/duzelis/{id}', [AdminController::class, 'showEditChild']);
$router->post('/admin/usaq/ad/{id}', [AdminController::class, 'updateChildName']);
$router->post('/admin/usaq/sifre/{id}', [AdminController::class, 'resetChildPassword']);
$router->post('/admin/usaq/sil/{id}', [AdminController::class, 'deleteChild']);
$router->get('/admin/komanda', [AdminController::class, 'team']);
$router->post('/admin/komanda', [AdminController::class, 'addModerator']);
$router->post('/admin/komanda/toggle/{id}', [AdminController::class, 'toggleAdmin']);
$router->post('/admin/komanda/sifre/{id}', [AdminController::class, 'resetStaffPassword']);
$router->post('/admin/komanda/rol/{id}', [AdminController::class, 'changeStaffRole']);

return $router;
