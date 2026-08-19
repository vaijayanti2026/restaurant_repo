<div class="footer">
    <div class="row justify-content-between align-items-center gy-2">
        <div class="col-md-4">
            <p class="font-size-sm mb-0 text-center text-md-left">
                <?php echo e(\App\Model\BusinessSetting::where(['key'=>'footer_text'])->first()->value); ?>

            </p>
        </div>
        <div class="col-md-8">
            <!-- List Dot -->
            <ul class="list-inline-menu justify-content-center justify-content-md-end">
                <li>
                    <a href="<?php echo e(route('admin.business-settings.restaurant.restaurant-setup')); ?>">
                        <span><?php echo e(translate('Business_Setup')); ?></span>
                        <img width="12" class="avatar-img rounded-0" src="<?php echo e(asset('public/assets/admin/img/icons/business_setup.png')); ?>" alt="Image Description">
                    </a>
                </li>

                <li>
                    <a href="<?php echo e(route('admin.settings')); ?>">
                        <span><?php echo e(translate('profile')); ?></span>
                        <img width="12" class="avatar-img rounded-0" src="<?php echo e(asset('public/assets/admin/img/icons/profile.png')); ?>" alt="Image Description">
                    </a>
                </li>

                <li>
                    <a href="<?php echo e(route('admin.dashboard')); ?>">
                        <span><?php echo e(translate('Home')); ?></span>
                        <img width="12" class="avatar-img rounded-0" src="<?php echo e(asset('public/assets/admin/img/icons/home.png')); ?>" alt="Image Description">
                    </a>
                </li>
                <li>
                    <label class="badge badge-soft-c1 font-weight-bold fz-12 mb-0">
                        Software Version : <?php echo e(env('SOFTWARE_VERSION')); ?>

                    </label>
                </li>
            </ul>
            <!-- End List Dot -->
        </div>
    </div>
</div>
<?php /**PATH D:\composer-cache (1)\resources\views/layouts/admin/partials/_footer.blade.php ENDPATH**/ ?>