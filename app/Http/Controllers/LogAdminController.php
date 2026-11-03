<?php
namespace App\Http\Controllers;

use App\Services\LogReaderService;
use Illuminate\Http\Request;

class LogAdminController extends Controller
{
    public function index(Request $request)
    {
        // Definir valores por defecto desde la Request
        $limit = (int) $request->input('limit', 15);  // Cuántos logs traer
        $offset = (int) $request->input('offset', 0); // Desde qué número de registro empezar

        $logReader = new LogReaderService('auditoria'); // Especifica tu canal aquí
        
        $logs = $logReader->getLogs($limit, $offset);
        $totalLogs = $logReader->getTotalCount();

        return view('admin.logs', [
            'logs' => $logs,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $totalLogs,
        ]);
    }
}
