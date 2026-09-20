@servers(['localhost' => '127.0.0.1'])
@task('deploy', ['on' => 'web'])
    cd /Users/rahees/Project/v3_investorrportal
    php artisan migrate --force
@endstory
@task('deploy', ['on' => 'localhost'])
    cd /Users/rahees/Project/v3_investorrportal
    php artisan migrate --force
@endtask
@task('update-code')
    cd /Users/rahees/Project/v3_investorrportal
    git pull origin master
@endtask
@task('restart-queues', ['on' => 'workers'])
    cd /Users/rahees/Project/v3_investorrportal
    php artisan queue:restart
@endtask
@task('install-dependencies')
    cd /Users/rahees/Project/v3_investorrportal
    composer install
@endtask