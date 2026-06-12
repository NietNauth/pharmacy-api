<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class PrescriptionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $prescriptions = Prescription::with('branch')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return $this->paginated($prescriptions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'image_url' => ['required_without:file', 'url', 'max:512'],
            'file' => ['required_without:image_url', 'image', 'max:5120'],
            'doctor_name' => ['nullable', 'string', 'max:150'],
            'hospital' => ['nullable', 'string', 'max:150'],
            'issued_date' => ['nullable', 'date'],
            'expires_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
        ]);

        $url = $validated['image_url'] ?? null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('prescriptions', 'public');
            $url = asset('storage/' . $path);
        }

        $prescription = Prescription::create([
            'user_id' => $request->user()->id,
            'image_url' => $url,
            'doctor_name' => $validated['doctor_name'] ?? null,
            'hospital' => $validated['hospital'] ?? null,
            'issued_date' => $validated['issued_date'] ?? null,
            'expires_date' => $validated['expires_date'] ?? null,
            'status' => 'pending',
        ]);

        // Notify staff
        $staff = User::getStaff();
        Notification::send($staff, new SystemNotification(
            'Đơn thuốc mới',
            "Có đơn thuốc mới từ khách hàng {$request->user()->full_name}.",
            'prescription',
            $prescription->id
        ));

        return $this->success($prescription, 'Đã gửi đơn thuốc thành công.', 201);
    }

    public function show(Request $request, $id)
    {
        $prescription = Prescription::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return $this->success($prescription);
    }

    public function pending()
    {
        $prescriptions = Prescription::with('user')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return $this->paginated($prescriptions);
    }

    public function review(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reject_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'reject_reason.required_if' => 'Lý do từ chối là bắt buộc khi từ chối đơn thuốc.',
        ]);

        $prescription = Prescription::findOrFail($id);
        $prescription->update([
            'status' => $validated['status'],
            'reject_reason' => $validated['reject_reason'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->success($prescription, 'Đã cập nhật trạng thái đơn thuốc.');
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);

        $prescription = Prescription::findOrFail($id);
        $prescription->update([
            'branch_id' => $request->branch_id
        ]);

        // Notify branch staff
        $staff = User::getStaff($request->branch_id);
        Notification::send($staff, new SystemNotification(
            'Đơn thuốc mới được phân phối',
            "Đơn thuốc của {$prescription->user->full_name} đã được phân phối về chi nhánh của bạn.",
            'prescription',
            $prescription->id
        ));

        return $this->success($prescription, 'Đã phân phối đơn thuốc về chi nhánh.');
    }
}
