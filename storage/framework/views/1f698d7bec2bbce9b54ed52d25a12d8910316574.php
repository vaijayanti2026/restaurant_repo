

<?php $__env->startSection('title', translate('All Orders')); ?>

<?php $__env->startSection('content'); ?>
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-1">
                <img width="20" class="avatar-img" src="<?php echo e(asset('public/assets/admin/img/icons/all_orders.png')); ?>" alt="">
                <span class="page-header-title"><?php echo e(translate('All Orders')); ?></span>
            </h2>
            <span class="badge badge-soft-dark rounded-50 fz-14"><?php echo e($orders->total()); ?></span>
        </div>

        <div class="card mb-3"><div class="card-body">
            <form method="GET" action="<?php echo e(route('admin.orders.all-orders')); ?>">
                <div class="row gy-3 gx-2 align-items-end">
                    <div class="col-md-3"><label><?php echo e(translate('Search')); ?></label><input type="search" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="<?php echo e(translate('Order ID or status')); ?>"></div>
                    <div class="col-md-3"><label><?php echo e(translate('Select_Branch')); ?></label><select class="form-control" name="branch"><option value=""><?php echo e(translate('all')); ?> <?php echo e(translate('branch')); ?></option><?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($branch->id); ?>" <?php echo e((string) $branchId === (string) $branch->id ? 'selected' : ''); ?>><?php echo e($branch->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    <div class="col-md-2"><label><?php echo e(translate('Start Date')); ?></label><input type="date" class="form-control" name="from" value="<?php echo e($from); ?>"></div>
                    <div class="col-md-2"><label><?php echo e(translate('End Date')); ?></label><input type="date" class="form-control" name="to" value="<?php echo e($to); ?>"></div>
                    <div class="col-md-2 d-flex gap-2"><a href="<?php echo e(route('admin.orders.all-orders')); ?>" class="btn btn-secondary flex-grow-1"><?php echo e(translate('Clear')); ?></a><button type="submit" class="btn btn-primary flex-grow-1"><?php echo e(translate('Show Data')); ?></button></div>
                </div>
            </form>
        </div></div>

        <div class="card"><div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light"><tr><th><?php echo e(translate('SL')); ?></th><th><?php echo e(translate('Order ID')); ?></th><th><?php echo e(translate('Date')); ?></th><th><?php echo e(translate('Customer')); ?></th><th><?php echo e(translate('Branch')); ?></th><th><?php echo e(translate('Amount')); ?></th><th><?php echo e(translate('Status')); ?></th><th><?php echo e(translate('Action')); ?></th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e((($orders->currentPage() - 1) * $orders->perPage()) + $key + 1); ?></td>
                        <td><a href="<?php echo e(route('admin.orders.details', ['id' => $order->id])); ?>"><?php echo e($order->id); ?></a></td>
                        <td><?php echo e(date('d M Y', strtotime($order->created_at))); ?></td>
                       <td><?php if($order->customer): ?><a class="text-body text-capitalize" href="<?php echo e(route('admin.customer.view', [$order->user_id])); ?>"><?php echo e($order->customer->f_name); ?> <?php echo e($order->customer->l_name); ?></a><?php else: ?><label class="badge badge-soft-info"><?php echo e(translate('Walking customer')); ?></label><?php endif; ?></td>
                        <td><label class="badge badge-soft-primary"><?php echo e($order->branch ? $order->branch->name : translate('Branch deleted!')); ?></label></td>
                        <td><?php echo e(\App\CentralLogics\Helpers::set_symbol($order->invoice_total)); ?></td>
                        <td><span class="badge badge-soft-info ml-2 ml-sm-3"><span class="legend-indicator bg-info"></span><?php echo e(translate(str_replace('_', ' ', $order->order_status))); ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(route('admin.orders.details', ['id' => $order->id])); ?>"><i class="tio-visible"></i> <?php echo e(translate('view')); ?></a>
                                <a class="btn btn-outline-success btn-sm" href="<?php echo e(route('admin.orders.generate-invoice', [$order->id])); ?>" target="_blank"><i class="tio-download"></i> <?php echo e(translate('invoice')); ?></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-center py-4"><?php echo e(translate('No data found')); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($orders->hasPages()): ?><div class="card-footer"><?php echo e($orders->links()); ?></div><?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\composer-cache (1)\resources\views/admin-views/order/all-orders.blade.php ENDPATH**/ ?>