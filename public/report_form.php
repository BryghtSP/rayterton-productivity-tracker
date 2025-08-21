<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();
include __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
  <div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6 md:p-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Daily Report Input</h1>
      
      <form action="save_report.php" method="POST" enctype="multipart/form-data" class="space-y-6">      
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Date Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input type="date" name="report_date" value="<?php echo date('Y-m-d'); ?>" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                   required>
          </div>
          
          <!-- Job Type Select -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
            <select name="job_type" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    required>
              <option value="Program">Program</option>
              <option value="Website">Website</option>
              <option value="Mobile Apps">Mobile Apps</option>
              <option value="Training Materi">Training Materi</option>
              <option value="Mengajar">Mengajar</option>
              <option value="QA/Testing">QA/Testing</option>
              <option value="UI/UX">UI/UX</option>
              <option value="DevOps">DevOps</option>
              <option value="Dokumentasi">Dokumentasi</option>
            </select>
          </div>
        </div>

        <!-- Title Input -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Judul/Menu/Layar</label>
          <input type="text" name="title" 
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                 required>
        </div>

        <!-- Description Textarea -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Status Select -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
              <option value="Progress">Progress</option>
              <option value="Selesai">Selesai</option>
            </select>
          </div>
          
          <!-- Proof Link Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bukti (URL repo/screenshot)</label>
            <input type="url" name="proof_link" placeholder="https://..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          </div>
        </div>
        <!-- Proof Image Upload -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Bukti (Foto)</label>
        <input type="file" name="proof_image" accept="image/*" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <p class="text-xs text-gray-500 mt-1">Upload gambar (jpg, png, jpeg)</p>
      </div>
        <!-- Submit Button -->
        <div class="pt-4">
          <button type="submit" 
                  class="w-full md:w-auto px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
            Save Report
          </button>
        </div>
      </form>
      
      <p class="mt-6 text-sm text-gray-500">
        Policy: Minimum 2 items per day, monthly target 50–88 items.
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>