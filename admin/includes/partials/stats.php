<ul class="slm_overview_stats">
    <li class="stats total-licenses">
        <div>
            <div class="icon"> <span class="dashicons dashicons-admin-network"></span> </div>
            <div class="info"> <span class="badge"> <?php echo SLM_Utility::get_total_licenses(); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Total licenses', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats total-licenses weekly">
        <div>
            <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
            <div class="info"> <span class="badge"> <?php echo SLM_Utility::getstats_licenses('date_created', 7); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Licenses this week', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats total-licenses monthly">
        <div>
            <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
            <div class="info"> <span class="badge"> <?php echo SLM_Utility::getstats_licenses('date_created', 31); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Licenses this month', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats active-licenses">
        <div>
            <div class="icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('active'); ?> </span></div>
        </div>
        <div class="description">
            <span><?php _e('Active licenses', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats pending-licenses">
        <div>
            <div class="icon"> <span class="dashicons dashicons-warning"></span> </div>
            <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('pending '); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Pending licenses', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats  blocked-licenses">
        <div>
            <div class="icon"> <span class="dashicons dashicons-dismiss"></span> </div>
            <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('blocked '); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Blocked licenses', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats logs">
        <div>
            <div class="icon"> <span class="dashicons dashicons-media-default"></span></span> </div>
            <div class="info"> <span class="badge"><?php echo SLM_Utility::count_logrequest(); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Logs saved', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats reminders">
        <div>
            <div class="icon"> <span class="dashicons dashicons-media-default"></span></span> </div>
            <div class="info"> <span class="badge"><?php echo SLM_Utility::count_emailsent(); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Reminders sent', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats expired-licenses">
        <div>
            <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
            <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('expired'); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Expired licenses', 'softwarelicensemanager');?></span>
        </div>
    </li>

    <li class="stats aboutoexpire">
        <div>
            <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
            <div class="info"> <span class="badge"> <?php echo SLM_Utility::get_lic_expiringsoon(); ?></span></div>
        </div>
        <div class="description">
            <span><?php _e('Licenses about to expire', 'softwarelicensemanager');?></span>
        </div>
    </li>
</ul>
<div class="h-spacer"></div>
<div class="clear clearfix"></div>
