{{-- resources/views/livewire/user/partials/team-member-modals.blade.php --}}

{{-- Edit Member Modal --}}
<div x-data="{
        showEditModal: @entangle('showEditModal').live,
        editForm: @entangle('editForm').live
     }"
     x-show="showEditModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-300"
         x-show="showEditModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="$wire.closeEditModal()">
        
        {{-- Modal Header --}}
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                <i class="fas fa-edit text-blue-600 mr-2"></i>
                Edit Anggota Tim
            </h3>
            <button @click="$wire.closeEditModal()"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <form wire:submit="updateMember" class="p-6 space-y-4">
            {{-- Name Field --}}
            <div>
                <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input wire:model.blur="editForm.name"
                       id="edit_name"
                       type="text" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('editForm.name') border-red-500 @enderror"
                       placeholder="Masukkan nama lengkap">
                @error('editForm.name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email Field --}}
            <div>
                <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input wire:model.blur="editForm.email"
                       id="edit_email"
                       type="email" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('editForm.email') border-red-500 @enderror"
                       placeholder="nama@email.com">
                @error('editForm.email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone Field --}}
            <div>
                <label for="edit_phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Nomor Telepon
                </label>
                <input wire:model.blur="editForm.phone"
                       id="edit_phone"
                       type="text" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('editForm.phone') border-red-500 @enderror"
                       placeholder="08xxxxxxxxxx">
                @error('editForm.phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Position Field --}}
            <div>
                <label for="edit_position" class="block text-sm font-medium text-gray-700 mb-2">
                    Posisi/Jabatan
                </label>
                <input wire:model.blur="editForm.position"
                       id="edit_position"
                       type="text" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('editForm.position') border-red-500 @enderror"
                       placeholder="Contoh: Developer, Designer, Manager">
                @error('editForm.position')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Leader Status --}}
            <div class="flex items-center">
                <input wire:model.live="editForm.is_leader"
                       id="edit_is_leader"
                       type="checkbox" 
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                <label for="edit_is_leader" class="ml-2 text-sm font-medium text-gray-700">
                    Jadikan sebagai Ketua Tim
                </label>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                Hanya bisa ada satu ketua tim. Jika dicentang, ketua sebelumnya akan otomatis diganti.
            </p>

            {{-- Modal Footer --}}
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" 
                        @click="$wire.closeEditModal()"
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Batal
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center"
                        wire:loading.attr="disabled"
                        wire:target="updateMember">
                    <span wire:loading.remove wire:target="updateMember">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </span>
                    <span wire:loading wire:target="updateMember">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Menyimpan...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div x-data="{
        showDeleteModal: false,
        memberToDelete: null,
        memberIdToDelete: null
     }"
     @confirm-delete.window="
        showDeleteModal = true;
        memberToDelete = $event.detail;
        memberIdToDelete = $event.detail.memberId;
        console.log('Modal opened for deletion, ID:', memberIdToDelete);
     "
     @reset-delete-modal.window="
        showDeleteModal = false;
        memberToDelete = null;
        memberIdToDelete = null;
     "
     x-show="showDeleteModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-300"
         x-show="showDeleteModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showDeleteModal = false">
        
        {{-- Modal Header --}}
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="bg-red-100 p-2 rounded-full mr-3">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900" x-text="memberToDelete?.title || 'Konfirmasi Hapus'"></h3>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="p-6">
            <p class="text-gray-600" x-text="memberToDelete?.message || 'Apakah Anda yakin ingin menghapus anggota ini?'"></p>
        </div>

        {{-- Modal Footer --}}
        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button @click="showDeleteModal = false"
                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                    x-text="memberToDelete?.cancelText || 'Batal'">
            </button>
            <button @click="
                    if (memberIdToDelete) {
                        console.log('Calling handleDeleteMember with ID:', memberIdToDelete);
                        $wire.handleDeleteMember(memberIdToDelete);
                        showDeleteModal = false;
                    } else {
                        console.error('Error: memberIdToDelete is null or undefined');
                        $wire.dispatch('showToast', {
                            type: 'error',
                            message: 'ID anggota tidak valid.'
                        });
                        showDeleteModal = false;
                    }"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                    wire:loading.attr="disabled"
                    wire:target="handleDeleteMember"
                    x-text="memberToDelete?.confirmText || 'Hapus'">
            </button>
        </div>
    </div>
</div>

{{-- Bulk Delete Confirmation Modal --}}
<div x-data="{
        showBulkDeleteModal: false,
        bulkDeleteData: null
     }"
     @confirm-bulk-delete.window="
        showBulkDeleteModal = true;
        bulkDeleteData = $event.detail;
     "
     x-show="showBulkDeleteModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-300"
         x-show="showBulkDeleteModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showBulkDeleteModal = false">
        
        {{-- Modal Header --}}
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="bg-red-100 p-2 rounded-full mr-3">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900" x-text="bulkDeleteData?.title || 'Konfirmasi Hapus'"></h3>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="p-6">
            <p class="text-gray-600" x-text="bulkDeleteData?.message || 'Apakah Anda yakin ingin menghapus anggota yang dipilih?'"></p>
        </div>

        {{-- Modal Footer --}}
        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button @click="showBulkDeleteModal = false"
                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                    x-text="bulkDeleteData?.cancelText || 'Batal'">
            </button>
            <button @click="
                    if (bulkDeleteData?.memberIds) {
                        $wire.executeBulkDelete(bulkDeleteData.memberIds);
                        showBulkDeleteModal = false;
                    }"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                    wire:loading.attr="disabled"
                    wire:target="executeBulkDelete"
                    x-text="bulkDeleteData?.confirmText || 'Hapus'">
            </button>
        </div>
    </div>
</div>

{{-- Member Detail Modal (View Only) --}}
<div x-data="{
        showDetailModal: false,
        memberDetail: null
     }"
     @show-member-detail-modal.window="
        memberDetail = $event.detail.member;
        showDetailModal = true;
        console.log('Member detail modal opened:', memberDetail);
     "
     x-show="showDetailModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 transform transition-all duration-300"
         x-show="showDetailModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showDetailModal = false">
        
        {{-- Modal Header --}}
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                <i class="fas fa-user text-blue-600 mr-2"></i>
                Detail Anggota
            </h3>
            <button @click="showDetailModal = false"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6" x-show="memberDetail">
            {{-- Member Avatar & Basic Info --}}
            <div class="text-center mb-6">
                <div class="relative inline-block">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-2xl mx-auto mb-3">
                        <span x-text="memberDetail?.name?.substring(0, 2).toUpperCase()"></span>
                    </div>
                    <div x-show="memberDetail?.is_leader" class="absolute -top-1 -right-1 bg-yellow-400 rounded-full p-1">
                        <i class="fas fa-crown text-yellow-800 text-xs"></i>
                    </div>
                </div>
                <h4 class="text-xl font-bold text-gray-900" x-text="memberDetail?.name"></h4>
                <p class="text-blue-600 font-medium" x-text="memberDetail?.position || 'Tidak ada posisi'"></p>
                <div x-show="memberDetail?.is_leader" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mt-2">
                    <i class="fas fa-star mr-1"></i>
                    Ketua Tim
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="space-y-4">
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Email</p>
                        <p class="font-medium text-gray-900" x-text="memberDetail?.email"></p>
                    </div>
                </div>
                
                <div x-show="memberDetail?.phone" class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <i class="fas fa-phone text-gray-400 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Telepon</p>
                        <p class="font-medium text-gray-900" x-text="memberDetail?.phone"></p>
                    </div>
                </div>
                
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <i class="fas fa-calendar-plus text-gray-400 mr-3 w-5"></i>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Bergabung</p>
                        <p class="font-medium text-gray-900" x-text="memberDetail?.joined_date"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="flex justify-end p-6 border-t border-gray-200">
            <button @click="showDetailModal = false"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Bulk Position Update Modal --}}
<div x-data="{
        showBulkPositionModal: false,
        newPosition: ''
     }"
     @show-bulk-position-modal.window="
        showBulkPositionModal = true;
        newPosition = '';
     "
     x-show="showBulkPositionModal"
     x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all duration-300"
         x-show="showBulkPositionModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showBulkPositionModal = false">
        
        {{-- Modal Header --}}
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                <i class="fas fa-users-cog text-blue-600 mr-2"></i>
                Update Posisi Massal
            </h3>
            <button @click="showBulkPositionModal = false"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6">
            <div class="mb-4">
                <p class="text-gray-600 mb-2">
                    Anda akan mengubah posisi untuk <span class="font-semibold" x-text="$wire.getSelectionCount()"></span> anggota yang dipilih.
                </p>
            </div>
            
            <div>
                <label for="bulk_position" class="block text-sm font-medium text-gray-700 mb-2">
                    Posisi Baru
                </label>
                <input x-model="newPosition"
                       id="bulk_position"
                       type="text"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Masukkan posisi baru">
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button @click="showBulkPositionModal = false"
                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Batal
            </button>
            <button @click="
                    if (newPosition.trim()) {
                        $wire.bulkUpdatePositions(newPosition.trim());
                        showBulkPositionModal = false;
                    }"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    :disabled="!newPosition.trim()">
                Update Posisi
            </button>
        </div>
    </div>
</div>