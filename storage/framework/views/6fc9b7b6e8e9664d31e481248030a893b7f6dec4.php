<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered">
        <div class="navbar-vertical-container text-capitalize">
            <div class="navbar-vertical-footer-offset">
                <div class="navbar-brand-wrapper justify-content-between">
                    <!-- Logo -->
                    <?php ($restaurant_logo=\App\Model\BusinessSetting::where(['key'=>'logo'])->first()->value); ?>
                    <a class="navbar-brand" href="<?php echo e(route('admin.dashboard')); ?>" aria-label="Front">
                        <img class="navbar-brand-logo" style="object-fit: contain;"
                             onerror="this.src='<?php echo e(asset('public/assets/admin/img/160x160/img2.jpg')); ?>'"
                             src="<?php echo e(asset('storage/app/public/restaurant/'.$restaurant_logo)); ?>"
                             alt="Logo">
                        <img class="navbar-brand-logo-mini" style="object-fit: contain;"
                             onerror="this.src='<?php echo e(asset('public/assets/admin/img/160x160/img2.jpg')); ?>'"
                             src="<?php echo e(asset('storage/app/public/restaurant/'.$restaurant_logo)); ?>" alt="Logo">
                    </a>
                    <!-- End Logo -->

                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip" data-placement="right" title="" data-original-title="Collapse"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align" data-template="<div class=&quot;tooltip d-none d-sm-block&quot; role=&quot;tooltip&quot;><div class=&quot;arrow&quot;></div><div class=&quot;tooltip-inner&quot;></div></div>" data-toggle="tooltip" data-placement="right" title="" data-original-title="Expand"></i>
                    </button>
                    <!-- End Navbar Vertical Toggle -->

                    <div class="navbar-nav-wrap-content-left d-none d-xl-block">
                        <!-- Navbar Vertical Toggle -->
                        <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                            <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip" data-placement="right" title="" data-original-title="Collapse"></i>
                            <i class="tio-last-page navbar-vertical-aside-toggle-full-align"></i>
                        </button>
                        <!-- End Navbar Vertical Toggle -->
                    </div>
                </div>

                <!-- Content -->
                <div class="navbar-vertical-content">
                    <div class="sidebar--search-form py-3">
                        <div class="search--form-group">
                            <button type="button" class="btn"><i class="tio-search"></i></button>
                            <input type="text" class="js-form-search form-control form--control" id="search-bar-input" placeholder="Search Menu...">
                        </div>
                    </div>

                    <ul class="navbar-nav navbar-nav-lg nav-tabs">
                        <!-- Dashboards -->

                        <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin')?'show':''); ?>">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                               href="<?php echo e(route('admin.dashboard')); ?>" title="<?php echo e(translate('Dashboards')); ?>">
                                <i class="tio-home-vs-1-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('dashboard')); ?>

                                    </span>
                            </a>
                        </li>

                        <!-- End Dashboards -->

                        <?php if(Helpers::module_permission_check('pos_management')): ?>

                            <!-- POS -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/pos/*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                    <i class="tio-shopping nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('POS')); ?></span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/pos*')?'block':'none'); ?>">
                                    <li class="nav-item <?php echo e(Request::is('admin/pos')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.pos.index')); ?>"
                                           title="<?php echo e(translate('pos')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('New_Sale')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/pos/orders')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.pos.orders')); ?>" title="<?php echo e(translate('orders')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('orders')); ?>

                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::Pos()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- End POS -->
                        <?php endif; ?>

                        <?php if(Helpers::module_permission_check('order_management')): ?>
                        <?php ($orderMgt=\App\CentralLogics\Helpers::get_business_settings('customize_order_menu')); ?>
                            <li class="nav-item">
                                <small
                                    class="nav-subtitle"><?php echo e(translate('order')); ?> <?php echo e(translate('management')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/orders/list/*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                    <i class="tio-shopping-cart nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('order')); ?>

                                    </span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/order*')?'block':'none'); ?>">
                                    <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/all')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.orders.list',['all'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <span><?php echo e(translate('all')); ?></span>
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/pending')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['pending'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <span><?php echo e(translate('pending')); ?></span>
                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'pending'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/all-orders')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.orders.all-orders')); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <span><?php echo e(translate('All Orders')); ?></span>
                                                <span class="badge badge-soft-info badge-pill ml-1"><?php echo e(\App\Model\Order::count()); ?></span>
                                            </span>
                                        </a>
                                    </li>
                                    
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/confirmed')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['confirmed'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('confirmed')); ?>

                                                <!--    <span class="badge badge-soft-success badge-pill ml-1">-->
                                                <!--    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'confirmed'])->notSchedule()->count()); ?>-->
                                                <!--</span>-->
                                            </span>
                                        </a>
                                    </li>
                                    
                                    
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/processing')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['processing'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                    <?php echo e(translate('processing')); ?>

                                                    <span class="badge badge-soft-warning badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'processing'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/out_for_delivery')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['out_for_delivery'])); ?>"
                                           title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                    <?php echo e(translate('out_for_delivery')); ?>

                                                    <span class="badge badge-soft-warning badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'out_for_delivery'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/delivered')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['delivered'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                    <?php echo e(translate('delivered')); ?>

                                                    <span class="badge badge-soft-success badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'delivered'])->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/returned')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['returned'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                    <?php echo e(translate('returned')); ?>

                                                    <span class="badge badge-soft-danger badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'returned'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>
                                    
                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/failed')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['failed'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('failed_to_deliver')); ?>

                                                <span class="badge badge-soft-danger badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'failed'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>

                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/canceled')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['canceled'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('canceled')); ?>

                                                    <span class="badge badge-soft-dark badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where(['order_status'=>'canceled'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                     <?php if(isset($orderMgt['status']) && $orderMgt['status']=='1'): ?>

                                    <li class="nav-item <?php echo e(Request::is('admin/orders/list/schedule')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.orders.list',['schedule'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('scheduled')); ?>

                                                    <span class="badge badge-soft-info badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->notDineIn()->where('delivery_date','>',\Carbon\Carbon::now()->format('Y-m-d'))->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <!-- End Pages -->
                        <?php endif; ?>

                        <?php if(Helpers::module_permission_check('order_management')): ?>
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/table/order/list/*') || Request::is('admin/table/order/details/*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                    <i class="tio-shopping-cart nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('table order')); ?>

                                    </span>
                                    <label class="badge badge-danger"><?php echo e(translate('addon')); ?></label>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/table/order*')?'block':'none'); ?>">
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/list/all')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.table.order.list',['all'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('all')); ?>

                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->dineIn()->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/list/confirmed')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.table.order.list',['confirmed'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('confirmed')); ?>

                                                <span class="badge badge-soft-info badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->dineIn()->where(['order_status'=>'confirmed'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/list/cooking')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.table.order.list',['cooking'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('cooking')); ?>

                                                <span class="badge badge-soft-warning badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->dineIn()->where(['order_status'=>'cooking'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/list/done')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.table.order.list',['done'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('Ready_For_Serve')); ?>

                                                <span class="badge badge-soft-success badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->dineIn()->where(['order_status'=>'done'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/list/completed')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.table.order.list',['completed'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('completed')); ?>

                                                <span class="badge badge-soft-success badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->dineIn()->where(['order_status'=>'completed'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/list/canceled')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.table.order.list',['canceled'])); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('canceled')); ?>

                                                <span class="badge badge-soft-danger badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::notPos()->dineIn()->where(['order_status'=>'canceled'])->notSchedule()->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/order/running')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.table.order.running')); ?>" title="">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate sidebar--badge-container">
                                                <?php echo e(translate('on_table')); ?>

                                                <span class="badge badge-soft-success badge-pill ml-1">
                                                    <?php echo e(\App\Model\Order::with('table_order')->whereHas('table_order', function($q){
                                                                    $q->where('branch_table_token_is_expired', 0);
                                                                })->count()); ?>

                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- End Pages -->
                        <?php endif; ?>

                        <?php if(Helpers::module_permission_check('product_management')): ?>
                            <li class="nav-item">
                                <small
                                    class="nav-subtitle"><?php echo e(translate('product')); ?> <?php echo e(translate('management')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>


                            <!-- Pages -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/category*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                >
                                    <i class="tio-category nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('category')); ?> <?php echo e(translate('setup')); ?></span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/category*')?'block':'none'); ?>">
                                    <li class="nav-item <?php echo e(Request::is('admin/category/add')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.category.add')); ?>"
                                           title="<?php echo e(translate('add new category')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('category')); ?></span>
                                        </a>
                                    </li>

                                    <li class="nav-item <?php echo e(Request::is('admin/category/add-sub-category')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.category.add-sub-category')); ?>"
                                           title="<?php echo e(translate('add new sub category')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('sub_category')); ?></span>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            <!-- End Pages -->


                            <!-- Pages -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/addon*') ||Request::is('admin/product*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                >
                                    <i class="tio-premium-outlined nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('product')); ?> <?php echo e(translate('setup')); ?></span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/product*') || Request::is('admin/addon*') || Request::is('admin/attribute*') || Request::is('admin/reviews*')?'block':'none'); ?>">
                                    <li class="nav-item <?php echo e(Request::is('admin/attribute*')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.attribute.add-new')); ?>"
                                           title="<?php echo e(translate('Add product attribute')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate"><?php echo e(translate('Product_Attributes')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/addon*')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.addon.add-new')); ?>"
                                           title="<?php echo e(translate('add addon')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate"><?php echo e(translate('Product_Addon')); ?></span>
                                        </a>
                                    </li>
                                     <li class="nav-item <?php echo e(Request::is('admin/badge*')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.badge.add-new')); ?>"
                                           title="<?php echo e(translate('add badge')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate"><?php echo e(translate('Add Badge')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/product/list') || Request::is('admin/product/edit*') ?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.product.list')); ?>" title="<?php echo e(translate('product_list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('product_list')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/product/bulk-import')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.product.bulk-import')); ?>" title="<?php echo e(translate('bulk import')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('bulk_import')); ?></span>
                                        </a>
                                    </li>
                                    
                                    <li class="nav-item <?php echo e(Request::is('admin/product/bulk-export')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.product.bulk-export')); ?>" title="<?php echo e(translate('bulk export')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('bulk_export')); ?></span>
                                        </a>
                                    </li>
                                    <!-- REVIEWS -->
                                    <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/reviews*')?'active':''); ?>">
                                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.reviews.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('product')); ?> <?php echo e(translate('reviews')); ?>

                                    </span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- End Pages -->
                        <?php endif; ?>

<!-- Reward -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/reward*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.reward.list')); ?>"
                                >
                                    <i class="tio-image nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('Reward Setup')); ?></span>
                                </a>
                            </li>
                            

                        <?php if(Helpers::module_permission_check('promotion_management')): ?>
                            <li class="nav-item">
                                <small
                                    class="nav-subtitle"><?php echo e(translate('promotion')); ?> <?php echo e(translate('management')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                            <!-- BANNER -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/poster*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.poster.list')); ?>"
                                >
                                    <i class="tio-image nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('poster')); ?></span>
                                </a>
                            </li>
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/banner*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.banner.list')); ?>"
                                >
                                    <i class="tio-image nav-icon"></i>
                                    <span
                                        class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('banner')); ?></span>
                                </a>
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                
                            </li>
                            <!-- shohel -->
                              <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/advertisement*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.advertisement.list')); ?>">
                                    <i class="tio-notifications nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('Advertisement Setup')); ?></span>
                                </a>
                            </li>

                            <!-- COUPON -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/coupon*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.coupon.add-new')); ?>">
                                    <i class="tio-gift nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('coupon')); ?></span>
                                </a>
                            </li>
                            <!-- Tag -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/tag*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.tag.list')); ?>">
                                    <i class="tio-gift nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('Tags')); ?></span>
                                </a>
                            </li>

                            <!-- NOTIFICATION -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/notification*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.notification.add-new')); ?>">
                                    <i class="tio-notifications nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('send')); ?> <?php echo e(translate('notification')); ?>

                                    </span>
                                </a>
                            </li>
                        <?php endif; ?>




                        <?php if(Helpers::module_permission_check('help_and_support_management')): ?>
                            <li class="nav-item">
                                <small class="nav-subtitle"
                                       title="Layouts"><?php echo e(translate('Help_&_Support_Section')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>
                            <!-- MESSAGE -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/message*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.message.list')); ?>">
                                    <i class="tio-messages nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('messages')); ?>

                                    </span>
                                </a>
                            </li>
                        <?php endif; ?>



                        <?php if(Helpers::module_permission_check('report_and_analytics_management')): ?>
                            <li class="nav-item">
                                <small class="nav-subtitle"
                                       title="<?php echo e(translate('report and analytics')); ?>"><?php echo e(translate('report_and_analytics')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                            <!-- Pages -->
                                    <li class="nav-item <?php echo e(Request::is('admin/report/earning')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.report.earning')); ?>">
                                            <i class="tio-chart-pie-1 nav-icon"></i>
                                            <span
                                                class="text-truncate"><?php echo e(translate('earning')); ?> <?php echo e(translate('report')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/report/order')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.report.order')); ?>"
                                        >
                                            <i class="tio-chart-bar-2 nav-icon"></i>
                                            <span
                                                class="text-truncate"><?php echo e(translate('order')); ?> <?php echo e(translate('report')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/report/deliveryman-report')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.report.deliveryman_report')); ?>"
                                        >

                                            <i class="tio-chart-donut-2 nav-icon"></i>
                                            <span
                                                class="text-truncate"><?php echo e(translate('DeliveryMan Report')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/report/product-report')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.report.product-report')); ?>"
                                        >
                                            <i class="tio-chart-bubble nav-icon"></i>
                                            <span
                                                class="text-truncate"><?php echo e(translate('product')); ?> <?php echo e(translate('report')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/report/sale-report')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.report.sale-report')); ?>">
                                            <i class="tio-chart-bar-1 nav-icon"></i>
                                            <span class="text-truncate"><?php echo e(translate('sale')); ?> <?php echo e(translate('report')); ?></span>
                                        </a>
                                    </li>


                            <!-- End Pages -->
                        <?php endif; ?>



                        <!-- User Management -->
                        <?php if(Helpers::module_permission_check('user_management')): ?>
                            <li class="nav-item <?php echo e((Request::is('admin/employee*') || Request::is('admin/custom-role*'))?'scroll-here':''); ?>">
                                <small class="nav-subtitle"><?php echo e(translate('user_management')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/customer/transaction') || Request::is('admin/customer/list') || Request::is('admin/customer/view*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                    <i class="tio-poi-user nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('customer')); ?>

                                    </span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: <?php echo e(Request::is('admin/customer/transaction') || Request::is('admin/customer/list')  || Request::is('admin/customer/view*')?'block':''); ?>; top: 831.076px;">
                                    <li class="nav-item <?php echo e(Request::is('admin/customer/list') || Request::is('admin/customer/view*') ? 'active' : ''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.customer.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('list')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/customer/transaction')? 'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.customer.transaction')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('point_history')); ?></span>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            <!-- Pages -->
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/customer/subscribed-email*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link"
                                   href="<?php echo e(route('admin.customer.subscribed_emails')); ?>">
                                    <i class="tio-email-outlined nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('Subscribed Emails')); ?>

                                    </span>
                                </a>
                            </li>
                            <!-- End Pages -->
                        <?php endif; ?>
                        <?php ($userMgt=\App\CentralLogics\Helpers::get_business_settings('develivery_employee_chef_settings')); ?>
                        

                        <?php if(Helpers::module_permission_check('user_management')): ?>
                        <?php if(isset($userMgt['status']) && $userMgt['status']=='1'): ?>
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/delivery-man*')? 'active' : ''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                    <i class="tio-user nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            <?php echo e(translate('deliveryman')); ?>

                                        </span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display:  <?php echo e(Request::is('admin/delivery-man*')? 'block' : ''); ?>; top: 831.076px;">
                                    <li class="nav-item <?php echo e(Request::is('admin/delivery-man/list')? 'active' : ''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.delivery-man.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('Delivery_Man_List')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/delivery-man/add')? 'active' : ''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.delivery-man.add')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('Add_New_Delivery_Man')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/delivery-man/reviews/list')? 'active' : ''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.delivery-man.reviews.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('Delivery_Man_Reviews')); ?></span>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if(Helpers::module_permission_check('user_management')): ?>


































                            <?php if(isset($userMgt['status']) && $userMgt['status']=='1'): ?>
                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/custom-role*') || Request::is('admin/employee*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="<?php echo e(translate('Employees')); ?>">
                                    <i class="tio-incognito nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            <?php echo e(translate('Employees')); ?>

                                        </span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub " style="display: <?php echo e(Request::is('admin/custom-role*') || Request::is('admin/employee*')?'block':''); ?>">

                                    <li class="nav-item <?php echo e(Request::is('admin/custom-role*')? 'active': ''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.custom-role.create')); ?>" title="<?php echo e(translate('Employee Role Setup')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                                    <?php echo e(translate('Employee Role Setup')); ?></span>
                                        </a>
                                    </li>

                                    <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/employee*')?'active':''); ?>">
                                        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" title="<?php echo e(translate('Employee Setup')); ?>">
                                            <span class="tio-user mr-2"></span>
                                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                                    <?php echo e(translate('Employee Setup')); ?>

                                                </span>
                                        </a>
                                        <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: <?php echo e(Request::is('admin/employee*')?'block':''); ?>">
                                            <li class="nav-item <?php echo e(Request::is('admin/employee/add-new')?'active':''); ?>">
                                                <a class="nav-link " href="<?php echo e(route('admin.employee.add-new')); ?>" title="<?php echo e(translate('add new')); ?>">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate"><?php echo e(translate('add new')); ?></span>
                                                </a>
                                            </li>
                                            <li class="nav-item <?php echo e(Request::is('admin/employee/list')?'active':''); ?>">
                                                <a class="nav-link" href="<?php echo e(route('admin.employee.list')); ?>" title="<?php echo e(translate('List')); ?>">
                                                    <span class="tio-circle nav-indicator-icon"></span>
                                                    <span class="text-truncate"><?php echo e(translate('list')); ?></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if(Helpers::module_permission_check('user_management')): ?>
                        <?php if(isset($userMgt['status']) && $userMgt['status']=='1'): ?>

                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/kitchen*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                   href="javascript:">
                                    <i class="tio-user nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            <?php echo e(translate('chef')); ?>

                                        </span>
                                    <label class="badge badge-danger"><?php echo e(translate('addon')); ?></label>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/kitchen*')?'block':'none'); ?>">
                                    <li class="nav-item <?php echo e(Request::is('admin/kitchen/add-new')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.kitchen.add-new')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('add_new')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/kitchen/list')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.kitchen.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('List')); ?></span>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- User Management  End-->



                    <!-- Table Management-->
                    <?php if(Helpers::module_permission_check('table_management')): ?>
                            <li class="nav-item">
                                <small class="nav-subtitle"><?php echo e(translate('table')); ?> <?php echo e(translate('section')); ?></small>
                                <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                            </li>

                            <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/table/list') || Request::is('admin/promotion/create') || Request::is('admin/table/index')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                    <i class="tio-gift nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            <?php echo e(translate('table')); ?>

                                        </span>
                                    <label class="badge badge-danger"><?php echo e(translate('addon')); ?></label>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="top: 391.823px; display: <?php echo e(Request::is('admin/table*') || Request::is('admin/promotion/*')?'block':''); ?>;">
                                    <li class="nav-item <?php echo e(Request::is('admin/table/list')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.table.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('list')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/table/index')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.table.index')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('availability')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/promotion/create')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.promotion.create')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('promotion_setup')); ?></span>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        <?php endif; ?>
                    <!-- Table Management End-->

                        <!-- BRANCH -->

                        <?php if(Helpers::module_permission_check('system_management')): ?>
                        <li class="nav-item">
                            <small class="nav-subtitle"><?php echo e(translate('system')); ?> <?php echo e(translate('setting')); ?></small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <!-- Business_Setup -->
                        <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/business-settings/restaurant*')?'active':''); ?>">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.business-settings.restaurant.restaurant-setup')); ?>">
                                <i class="tio-settings nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('Business_Setup')); ?></span>
                            </a>
                        </li>
                        <!-- END Business_Setup -->
                         <?php ($bconfig=\App\CentralLogics\Helpers::get_business_settings('branch_menu_settings')); ?>
                         <?php if(isset($bconfig['status']) && $bconfig['status']=='1'): ?>
                        <!--BRANCH SETUP -->
                        <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/branch*')?'active':''); ?>">
                                <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle"
                                   href="javascript:">
                                    <i class="tio-shop nav-icon"></i>
                                    <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                            <?php echo e(translate('Branch_Setup')); ?>

                                        </span>
                                </a>
                                <ul class="js-navbar-vertical-aside-submenu nav nav-sub"
                                    style="display: <?php echo e(Request::is('admin/branch*')?'block':'none'); ?>">
                                    <li class="nav-item <?php echo e(Request::is('admin/branch/add-new')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.branch.add-new')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('add_new')); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item <?php echo e(Request::is('admin/branch/list')?'active':''); ?>">
                                        <a class="nav-link" href="<?php echo e(route('admin.branch.list')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span class="text-truncate"><?php echo e(translate('List')); ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <!--END BRANCH SETUP -->
                        <?php endif; ?>

                        <!-- PAGE SETUP -->
                        <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/business-settings/web-app/social-media') || Request::is('admin/business-settings/page-setup/*')?'active':''); ?>">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-pages nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('Page & Media')); ?></span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub" style="display: <?php echo e(Request::is('admin/business-settings/web-app/social-media') || Request::is('admin/business-settings/page-setup*')?'block':'none'); ?>">
                                <!-- Page Setup -->
                                <li class="nav-item <?php echo e(Request::is('admin/business-settings/page-setup*')?'active':''); ?>">
                                    <a class="nav-link "
                                       href="<?php echo e(route('admin.business-settings.page-setup.about-us')); ?>">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate"><?php echo e(translate('Page_Setup')); ?></span>
                                    </a>
                                </li>

                                <!-- Social Link page -->
                                <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/business-settings/web-app/social-media')?'active':''); ?>">
                                    <a class="nav-link " href="<?php echo e(route('admin.business-settings.web-app.third-party.social-media')); ?>"
                                       title="<?php echo e(\App\CentralLogics\translate('Social Media Links')); ?>">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate"><?php echo e(\App\CentralLogics\translate('Social Media')); ?></span>
                                    </a>

                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <?php if(Helpers::module_permission_check('system_management')): ?>
                        <!-- 3rd Party -->
                        <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/business-settings/web-app/third-party*')?'active':''); ?>">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                               href="<?php echo e(route('admin.business-settings.web-app.payment-method')); ?>"
                            >
                                <i class="tio-running nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                        <?php echo e(translate('3rd_Party')); ?>

                                    </span>
                            </a>
                        </li>
                          <li class="nav-item <?php echo e(Request::is('admin/badge*')?'active':''); ?>">
                                        <a class="nav-link " href="<?php echo e(route('admin.reviews.add-new')); ?>"
                                           title="<?php echo e(translate('add badge')); ?>">
                                            <span class="tio-circle nav-indicator-icon"></span>
                                            <span
                                                class="text-truncate"><?php echo e(translate('Reviews')); ?></span>
                                        </a>
                            </li>
                        <!-- End 3rd Party -->
                        <!-- // shohel  -->
                        <li class="nav-item <?php echo e(Request::is('admin/rate/us*')?'active':''); ?>">
                            <a class="nav-link " href="<?php echo e(route('admin.rate-us.list')); ?>"
                           title="<?php echo e(translate('Rate Us')); ?>">
                            <span class="tio-circle nav-indicator-icon"></span>
                            <span
                                class="text-truncate"><?php echo e(translate('Rate Us')); ?></span>
                            </a>
                        </li>

                        <!-- SYSTEM SETTINGS -->
                        <li class="navbar-vertical-aside-has-menu <?php echo e(Request::is('admin/business-settings/web-app/system-setup*')?'active':''); ?>">
                            <a class="js-navbar-vertical-aside-menu-link nav-link" href="<?php echo e(route('admin.business-settings.web-app.system-setup.language.index')); ?>">
                                <i class="tio-security-on-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate"><?php echo e(translate('System Setup')); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <!--END SYSTEM SETTINGS -->


                        <li class="nav-item pt-10">
                            <div class=""></div>
                        </li>
                    </ul>
                </div>
                <!-- End Content -->
            </div>
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>




<?php $__env->startPush('script_2'); ?>
    <script>
        $(window).on('load' , function() {
            if($(".navbar-vertical-content li.active").length) {
                $('.navbar-vertical-content').animate({
                    scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
                }, 10);
            }
        });

        //Sidebar Menu Search
        var $rows = $('.navbar-vertical-content  .navbar-nav > li');
        $('#search-bar-input').keyup(function() {
            var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

            $rows.show().filter(function() {
                var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
                return !~text.indexOf(val);
            }).hide();
        });

    </script>
<?php $__env->stopPush(); ?>

<?php /**PATH D:\composer-cache (1)\resources\views/layouts/admin/partials/_sidebar.blade.php ENDPATH**/ ?>