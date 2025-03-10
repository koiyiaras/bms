<?php

global $wpdb;
$company_table = $wpdb->prefix . 'bms_company';
$company = $wpdb->get_row("SELECT * FROM $company_table LIMIT 1");

?>
    <div class="bms-container dashboard">
        <!--h2>Διαχείρηση - Dashboard</h2-->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"><?php echo esc_html($company->company_name); ?> 
                            <a href="/wp-admin/admin.php?page=bms%2Fincludes%2Fbms-company-page.php" class="btn btn-outline-primary btn-sm float-end">
                            <span class="dashicons dashicons-edit"></span>
                            </a>
                        </h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="card-text"><strong>Reg. number:</strong> <?php echo esc_html($company->registration); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="card-text"><strong>VAT Number:</strong> <?php echo esc_html($company->vat_number); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="dash-nav">
            <!--Row 1-->
            <div class="row text-center mt-4">
                <div class="col-md-6">
                    <a href="/clients/" class="btn btn-primary btn-lg btn-block mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Clients</h5>
                                <p class="card-text"><span class="dashicons dashicons-businessperson"></span></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/projects/" class="btn btn-warning btn-lg btn-block mb-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Projects</h5>
                                <p class="card-text"><span class="dashicons dashicons-welcome-widgets-menus"></span></i></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <!--Row 2-->
            <div class="row text-center mt-4">
                <div class="col-md-6">
                    <a href="/stock/" class="btn btn-success btn-lg btn-block mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Our Stock</h5>
                                <p class="card-text"><span class="dashicons dashicons-database"></span></i></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/tasks/" class="btn btn-danger btn-lg btn-block mb-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Tasks</h5>
                                <p class="card-text"><span class="dashicons dashicons-media-spreadsheet"></span></i></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <!--Row 3-->
            <div class="row text-center mt-4">
                <div class="col-md-6">
                    <a href="/quotes/" class="btn btn-purple btn-lg btn-block mb-4">
                        <div class="card bg-purple text-white">
                            <div class="card-body">
                                <h5 class="card-title">Quotes</h5>
                                <p class="card-text"><span class="dashicons dashicons-welcome-write-blog"></span></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/invoices/" class="btn btn-orange btn-lg btn-block mb-4">
                        <div class="card bg-orange text-white">
                            <div class="card-body">
                                <h5 class="card-title">Invoices</h5>
                                <p class="card-text"><span class="dashicons dashicons-format-aside"></span></i></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row text-center mt-4">
                <div class="col-md-6">
                    <a href="/balances/" class="btn btn-secondary btn-lg btn-block mb-4">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Balances</h5>
                                <p class="card-text"><span class="dashicons dashicons-media-spreadsheet"></span></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/wp-admin/admin.php?page=bms%2Fincludes%2Fbms-company-page.php" class="btn btn-dark btn-lg btn-block mb-4">
                        <div class="card bg-dark text-white">
                            <div class="card-body">
                                <h5 class="card-title">Settings</h5>
                                <p class="card-text"><span class="dashicons dashicons-admin-settings"></span></i></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
