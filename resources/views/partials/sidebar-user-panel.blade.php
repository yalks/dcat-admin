<!-- Sidebar user panel (optional) -->
<div class="user-panel">
    <div class="pull-left image">
        <img style="max-height:45px" src="{{ Dcat\Admin\Admin::user()->getAvatar() }}" class="img-circle">
    </div>
    <div class="pull-left info">
        <p>{{ Dcat\Admin\Admin::user()->name }}</p>
        <!-- Status -->
        <a href="#"><i class="fa fa-circle text-success"></i> {{ trans('admin.online') }}</a>
    </div>
</div>