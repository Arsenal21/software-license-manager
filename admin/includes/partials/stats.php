<ul class="slm_overview_stats">
    <li class="stats total-licenses">
        <div class="icon"> <span class="dashicons dashicons-admin-network"></span> </div>
        <div class="info"> <span class="badge"> <?php echo SLM_Utility::get_total_licenses(); ?></span> <span>Total licenses</span></div>
    </li>

    <li class="stats total-licenses weekly">
        <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
        <div class="info"> <span class="badge"> <?php echo SLM_Utility::getstats_licenses('date_created', 7); ?></span> <span>Licenses this week</span></div>
    </li>

    <li class="stats total-licenses monthly">
        <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
        <div class="info"> <span class="badge"> <?php echo SLM_Utility::getstats_licenses('date_created', 31); ?></span> <span>Licenses this month</span></div>
    </li>

    <li class="stats active-licenses">
        <div class="icon"><span class="dashicons dashicons-yes-alt"></span></div>
        <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('active'); ?> </span> <span>Active licenses</span></div>
    </li>

    <li class="stats pending-licenses">
        <div class="icon"> <span class="dashicons dashicons-warning"></span> </div>
        <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('pending '); ?></span> </span> <span>Pending licenses</span></div>
    </li>

    <li class="stats  blocked-licenses">
        <div class="icon"> <span class="dashicons dashicons-dismiss"></span> </div>
        <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('blocked '); ?></span> </span> <span>Blocked licenses</span></div>

    </li>

    <li class="stats logs">
        <div class="icon"> <span class="dashicons dashicons-media-default"></span></span> </div>
        <div class="info"> <span class="badge"><?php echo SLM_Utility::count_logrequest(); ?></span> </span> <span>Logs saved</span></div>

    </li>

    <li class="stats reminders">
        <div class="icon"> <span class="dashicons dashicons-media-default"></span></span> </div>
        <div class="info"> <span class="badge"><?php echo SLM_Utility::count_emailsent(); ?></span> </span> <span>Reminders sent</span></div>

    </li>

    <li class="stats expired-licenses">
        <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
        <div class="info"> <span class="badge"><?php echo SLM_Utility::count_licenses('expired'); ?></span></span> <span>Expired licenses</span></div>
    </li>

    <li class="stats aboutoexpire">
        <div class="icon"> <span class="dashicons dashicons-calendar-alt"></span> </div>
        <div class="info"> <span class="badge"> <?php echo SLM_Utility::get_lic_expiringsoon(); ?></span> <span>Licenses about to expire</span></div>
    </li>

</ul>
<div class="h-spacer"></div>
<div class="clear clearfix"></div>