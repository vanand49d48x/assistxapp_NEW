<?php
include '../includes.php';

if (!empty($_GET['refresh-balance'])) {
    $row = ORM::for_table($config['db']['pre'] . 'api_keys')->find_one($_GET['refresh-balance']);

    if ($row['type'] == 'stable-diffusion') {
        require_once ROOTPATH . '/includes/lib/StableDiffusion.php';

        $stableDiffusion = new StableDiffusion($row['api_key']);
        $response = $stableDiffusion->balance();
        $response = json_decode($response, true);

        $credits = 0;
        if (isset($response['credits'])) {
            $credits = round($response['credits'], 2);
        }
        $balance = $credits;
    } else {
        require_once ROOTPATH . '/includes/lib/orhanerday/open-ai/src/OpenAi.php';
        require_once ROOTPATH . '/includes/lib/orhanerday/open-ai/src/Url.php';

        $open_ai = new Orhanerday\OpenAi\OpenAi($row['api_key']);
        $response = $open_ai->used_balance();
        $response = json_decode($response, true);

        $uses = $hard_limit = 0;
        if (isset($response['total_usage'])) {
            $uses = round($response['total_usage']) / 100;
        }
        $response = $open_ai->balance_hard_limit();
        $response = json_decode($response, true);

        if (isset($response['hard_limit_usd'])) {
            $hard_limit = round($response['hard_limit_usd'] * 100) / 100;
        }

        $balance = "$$uses / $$hard_limit";
    }

    /* update balance */
    $api_key = ORM::for_table($config['db']['pre'] . 'api_keys')->find_one($_GET['refresh-balance']);
    $api_key->balance = $balance;
    $api_key->balance_last_update = date('Y-m-d H:i:s');
    $api_key->save();

    headerRedirect(parse_url(get_current_page_url(), PHP_URL_PATH));
}

$page_title = __('API Keys');
include '../header.php'; ?>

    <!-- Page Body Start-->
    <div class="page-body-wrapper">
<?php include '../sidebar.php'; ?>

    <!-- Page Sidebar Ends-->
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-lg-6 main-header">
                        <h2><?php _esc($page_title) ?></h2>
                        <h6 class="mb-0"><?php _e('admin panel') ?></h6>
                    </div>
                    <div class="col-lg-6 text-right">
                        <a href="<?php echo ADMINURL ?>global/settings.php#quick_ai_setting"
                           class="btn btn-primary ripple-effect">
                            <i class="icon-feather-settings"></i> <?php _e('Settings'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Container-fluid starts-->
        <div class="container-fluid">
            <div class="quick-card card">
                <div class="card-body">
                    <div class="dataTables_wrapper">
                        <table class="table table-striped" id="ajax_datatable" data-jsonfile="api_keys.php">
                            <thead>
                            <tr>
                                <th><?php _e('Title') ?></th>
                                <th class="no-sort"><?php _e('API Key') ?></th>
                                <th><?php _e('Type') ?></th>
                                <th><?php _e('Balance') ?>
                                    <i class="fa fa-info-circle"
                                       title="<?php _e('The balance will be automatically refreshed once a day.'); ?>"
                                       data-tippy-placement="top"></i>
                                </th>
                                <th><?php _e('Active') ?></th>
                                <th width="20" class="no-sort" data-priority="1"></th>
                                <th width="20" class="no-sort" data-priority="1">
                                    <div class="checkbox">
                                        <input type="checkbox" id="quick-checkbox-all">
                                        <label for="quick-checkbox-all"><span class="checkbox-icon"></span></label>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Container-fluid Ends-->
    </div>
    <div class="site-action">
        <div class="site-action-buttons">
            <button type="button" id="quick-delete-button" data-action="deleteAPIKeys"
                    class="btn btn-danger btn-floating animation-slide-bottom">
                <i class="icon icon-feather-trash-2" aria-hidden="true"></i>
            </button>
        </div>
        <button type="button" class="front-icon btn btn-primary btn-floating" data-url="panel/api_keys.php"
                data-toggle="slidePanel">
            <i class="icon-feather-plus animation-scale-up" aria-hidden="true"></i>
        </button>
        <button type="button" class="back-icon btn btn-primary btn-floating">
            <i class="icon-feather-x animation-scale-up" aria-hidden="true"></i>
        </button>
    </div>
    <script>
        var QuickMenu = {"page": "api-keys"};
    </script>

<?php ob_start() ?>
<?php
$footer_content = ob_get_clean();

include '../footer.php';