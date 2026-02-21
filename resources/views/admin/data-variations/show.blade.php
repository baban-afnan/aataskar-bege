<x-app-layout>
    <title>Manage {{ $serviceName }} - Data Services</title>

    <div class="content">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('admin.data-variations.index') }}" class="btn btn-icon btn-sm btn-light rounded-circle me-3">
                            <i class="mdi mdi-arrow-left"></i>
                        </a>
                        <div>
                            <h3 class="page-title text-primary mb-1 fw-bold">{{ $serviceName }}</h3>
                            <ul class="breadcrumb bg-transparent p-0 mb-0">
                                <li class="breadcrumb-item text-muted">Data Services</li>
                                <li class="breadcrumb-item active text-primary">Variations</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-primary" style="background-color: #0db4bd; border: none;" data-bs-toggle="modal" data-bs-target="#addVariationModal">
                        <i class="mdi mdi-plus me-1"></i> Add Variation
                    </button>
                </div>
            </div>
        </div>

        <!-- Variations Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="mdi mdi-format-list-bulleted me-2 text-primary"></i>Available Variations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">S/N</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>API service ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variations as $variation)
                                <tr>
                                    <td class="ps-4 fw-medium text-muted">{{ $variations->firstItem() + $loop->index }}</td>
                                    <td class="fw-bold text-dark">{{ $variation->name }}</td>
                                    <td><code class="text-primary">{{ $variation->variation_code }}</code></td>
                                    <td><span class="badge bg-light text-dark border">{{ $variation->service_id ?? 'N/A' }}</span></td>
                                    <td class="fw-bold">₦{{ number_format($variation->variation_amount, 2) }}</td>
                                    <td>
                                        @if($variation->status)
                                            <span class="badge bg-soft-success text-success border-0 rounded-pill px-3">
                                                <i class="mdi mdi-check-circle me-1" style="font-size: 10px;"></i>Active
                                            </span>
                                        @else
                                            <span class="badge bg-soft-danger text-danger border-0 rounded-pill px-3">
                                                <i class="mdi mdi-close-circle me-1" style="font-size: 10px;"></i>Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="action-icon d-inline-flex">
                                            <button class="btn btn-icon btn-sm btn-soft-info rounded-circle me-2 edit-variation-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editVariationModal"
                                                    data-id="{{ $variation->id }}"
                                                    data-name="{{ $variation->name }}"
                                                    data-amount="{{ $variation->variation_amount }}"
                                                    data-code="{{ $variation->variation_code }}"
                                                    data-fee="{{ $variation->convinience_fee }}"
                                                    data-sid="{{ $variation->service_id }}"
                                                    data-status="{{ $variation->status }}"
                                                    data-fixed="{{ $variation->fixedPrice }}">
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-icon btn-sm btn-soft-danger rounded-circle" onclick="confirmDelete('{{ $variation->id }}', '{{ $variation->name }}')">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                            <form id="delete-form-{{ $variation->id }}" action="{{ route('admin.data-variations.destroy', $variation) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">No variations found for this service.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($variations->hasPages())
                <div class="card-footer bg-white border-top-0 py-3">
                    {{ $variations->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Add Variation Modal -->
    <div class="modal fade" id="addVariationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Add New Variation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.data-variations.store') }}" method="POST" id="addVariationForm">
                    @csrf
                    <input type="hidden" name="service_id" value="{{ $serviceId }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Variation Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., 1GB Monthly" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₦)</label>
                                <input type="number" step="0.01" name="variation_amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fee (₦)</label>
                                <input type="number" step="0.01" name="convinience_fee" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Variation Code</label>
                            <input type="text" name="variation_code" class="form-control" placeholder="e.g., mtn-1gb" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Service ID (Optional)</label>
                            <input type="text" name="service_id" class="form-control" placeholder="External API ID">
                        </div>
                        <div class="d-flex gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="fixedPrice" value="1" checked>
                                <label class="form-check-label">Fixed Price</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Variation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Variation Modal -->
    <div class="modal fade" id="editVariationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Edit Variation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editVariationForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Variation Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (₦)</label>
                                <input type="number" step="0.01" name="variation_amount" id="edit_amount" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fee (₦)</label>
                                <input type="number" step="0.01" name="convinience_fee" id="edit_fee" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Variation Code</label>
                            <input type="text" name="variation_code" id="edit_code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Service ID (Optional)</label>
                            <input type="text" name="service_id" id="edit_sid" class="form-control">
                        </div>
                        <div class="d-flex gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" id="edit_status" value="1">
                                <label class="form-check-label">Active</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="fixedPrice" id="edit_fixed" value="1">
                                <label class="form-check-label">Fixed Price</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Variation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Modal Population
            const editButtons = document.querySelectorAll('.edit-variation-btn');
            const editForm = document.getElementById('editVariationForm');
            
            editButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    editForm.action = `/admin/data-variations/${id}`;
                    
                    document.getElementById('edit_name').value = this.dataset.name;
                    document.getElementById('edit_amount').value = this.dataset.amount;
                    document.getElementById('edit_fee').value = this.dataset.fee;
                    document.getElementById('edit_code').value = this.dataset.code;
                    document.getElementById('edit_sid').value = this.dataset.sid !== 'null' ? this.dataset.sid : '';
                    document.getElementById('edit_status').checked = this.dataset.status === '1';
                    document.getElementById('edit_fixed').checked = this.dataset.fixed === '1';
                });
            });

            // Form Confirmation
            const confirmAction = (formId) => {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure you want to do this? If yes, click then continue.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0db4bd',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, continue!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(formId).submit();
                    }
                });
                return false;
            };

            const addForm = document.getElementById('addVariationForm');
            if(addForm) {
                addForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('addVariationForm');
                });
            }

            if(editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAction('editVariationForm');
                });
            }
        });

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Delete Variation?',
                text: `Are you sure you want to delete ${name}? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>

    {{-- SweetAlert CDN --}}

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    <style>
        .bg-soft-success { background-color: rgba(34, 197, 94, 0.1); }
        .bg-soft-danger { background-color: rgba(239, 68, 68, 0.1); }
        .bg-soft-info { background-color: rgba(9, 180, 214, 0.1); }
    </style>
</x-app-layout>
