<?php

/*
 * This file is part of the "Composer Shared Package Plugin" package.
 *
 * https://github.com/Letudiant/composer-shared-package-plugin
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LEtudiant\Composer\Installer\Solver;

use Composer\Config;
use Composer\Downloader\FilesystemException;
use Composer\Installer\InstallerInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use LEtudiant\Composer\Installer\SharedPackageInstaller;
use LEtudiant\Composer\Util\SymlinkFilesystem;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class SharedPackageInstallerSolver implements InstallerInterface
{
    /**
     * @var SymlinkFilesystem
     */
    protected $filesystem;

    /**
     * @var SharedPackageSolver
     */
    protected $solver;

    /**
     * @var SharedPackageInstaller
     */
    protected $symlinkInstaller;

    /**
     * @var LibraryInstaller
     */
    protected $defaultInstaller;


    /**
     * @param SharedPackageSolver    $solver
     * @param SharedPackageInstaller $symlinkInstaller
     * @param LibraryInstaller       $defaultInstaller
     */
    public function __construct(
        SharedPackageSolver $solver,
        SharedPackageInstaller $symlinkInstaller,
        LibraryInstaller $defaultInstaller
    )
    {
        $this->solver           = $solver;
        $this->symlinkInstaller = $symlinkInstaller;
        $this->defaultInstaller = $defaultInstaller;
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     *
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        if ($this->solver->isSharedPackage($package)) {
            return $this->symlinkInstaller->getInstallPath($package);
        }

        return $this->defaultInstaller->getInstallPath($package);
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if ($this->solver->isSharedPackage($package)) {
            $this->symlinkInstaller->install($repo, $package);
        } else {
            $this->defaultInstaller->install($repo, $package);
        }
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if ($this->solver->isSharedPackage($package)) {
            return $this->symlinkInstaller->isInstalled($repo, $package);
        }

        return $this->defaultInstaller->isInstalled($repo, $package);
    }

    /**
     * {@inheritdoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        // If both packages are not shared
        if (!$this->solver->isSharedPackage($initial) && !$this->solver->isSharedPackage($target)) {
            $this->defaultInstaller->update($repo, $initial, $target);
        } else {
            if (!$repo->hasPackage($initial)) {
                throw new \InvalidArgumentException('Package is not installed : ' . $initial->getPrettyName());
            }

            $this->symlinkInstaller->update($repo, $initial, $target);
        }
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @throws FilesystemException
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if ($this->solver->isSharedPackage($package)) {
            if (!$repo->hasPackage($package)) {
                throw new \InvalidArgumentException('Package is not installed : ' . $package->getPrettyName());
            }

            $this->symlinkInstaller->uninstall($repo, $package);
        } else {
            $this->defaultInstaller->uninstall($repo, $package);
        }
    }

    /**
     * @param string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        // The solving process is in SharedPackageSolver::isSharedPackage() method

        return true;
    }

    /**
     * Downloads the files needed to later install the given package.
     *
     * @param  PackageInterface      $package     package instance
     * @param  PackageInterface      $prevPackage previous package instance in case of an update
     * @return PromiseInterface|null
     */
    public function download(PackageInterface $package, PackageInterface $prevPackage = null)
    {
        // NOOP
        return null;
    }

    /**
     * Do anything that needs to be done between all downloads have been completed and the actual operation is executed
     *
     * All packages get first downloaded, then all together prepared, then all together installed/updated/uninstalled. Therefore
     * for error recovery it is important to avoid failing during install/update/uninstall as much as possible, and risky things or
     * user prompts should happen in the prepare step rather. In case of failure, cleanup() will be called so that changes can
     * be undone as much as possible.
     *
     * @param  string                $type        one of install/update/uninstall
     * @param  PackageInterface      $package     package instance
     * @param  PackageInterface      $prevPackage previous package instance in case of an update
     * @return PromiseInterface|null
     */
    public function prepare($type, PackageInterface $package, PackageInterface $prevPackage = null)
    {
        // NOOP
        return null;
    }

    /**
     * Do anything to cleanup changes applied in the prepare or install/update/uninstall steps
     *
     * Note that cleanup will be called for all packages regardless if they failed an operation or not, to give
     * all installers a change to cleanup things they did previously, so you need to keep track of changes
     * applied in the installer/downloader themselves.
     *
     * @param  string                $type        one of install/update/uninstall
     * @param  PackageInterface      $package     package instance
     * @param  PackageInterface      $prevPackage previous package instance in case of an update
     * @return PromiseInterface|null
     */
    public function cleanup($type, PackageInterface $package, PackageInterface $prevPackage = null)
    {
        // NOOP
        return null;
    }
}
