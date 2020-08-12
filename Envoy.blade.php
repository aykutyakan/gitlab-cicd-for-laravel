@servers(['prod' => 'root@domain.com'])

@setup
    $repository = 'git@gitlab.example.com:<USERNAME>/sample-app.git';
    $releases_dir = '/var/www/html/app.com/releases';
    $app_dir = '/var/www/html/app.com';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
    $assetsFolder = $app_dir."/storage/app/assets";
@endsetup

@story('deploy')
clone_repository
run_composer
update_symlinks
run_artisan_for_optimize
remove_old_release
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
	chown -R apache:apache {{$new_release_dir}}
    git reset --hard {{ $commit }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current

    echo 'Linking assets folder'
    ln -nfs {{$assetsFolder}} {{$new_release_dir}}/public/assets
    
@endtask
@task('run_artisan_for_optimize') 
    echo "starting optimize artisan commands and exec migrate"
    cd {{ $new_release_dir }}
    php artisan migrate
@endtask
@task('remove_old_release')
	echo "removing old release the  folder of {{$release}}"
	cd {{ $releases_dir }}
	find [0-9]* -maxdepth 0 -type d -not -name "{{$release}}" -exec rm -r {} +
	
@endtask
