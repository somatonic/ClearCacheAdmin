<?php

/**
 * Clear Cache Admin
 * ================================================================================
 */


class ClearCacheAdmin extends Process{

    protected $exludeCacheDirFiles = array("Page", "MarkupHTMLPurifier", "MarkupCache");

    /**
     * Return information about this module (required)
     *
     */
    public static function getModuleInfo() {
        return array(
            'title' => 'Clear Cache Admin',
            'summary' => 'Tool that helps you clear page cache.',
            'version' => 5,
            'author' => 'Soma',
            'icon' => 'gear',
            'page' => array(
                'parent' => "setup",
                'name' => "clear-cache-admin",
                'title' => "Cache Admin",
                ),
            'useNavJSON' => true,
            'requires' => array('ProcessWire>=2.6.0'),
            'permission' => "clear-cache-admin",
            'permissions' => array("clear-cache-admin" => "Clear PW Caches from Admin Menu"),
        );
    }

    public function ___execute(){

        $modules = $this->wire("modules");

        $out = "";

        // clear page render cache form, we get this from the module's config inputfields for convenience
        $inputfields = $modules->PageRender->getModuleConfigInputfields(array());
        $form = $modules->InputfieldForm;
        $form->attr("action", "./");
        $submit = $modules->InputfieldSubmit;
        $submit->attr("name", "submitpagecache");
        $submit->attr("value", __("Clear now"));
        $submit->showIf = "clearCache=1";
        $form->add($inputfields);
        $form->add($submit);
        $out .= $form->render();

        // clear markup cache form, we get this from the module's config inputfields for convenience
        $inputfields = $modules->MarkupCache->getModuleConfigInputfields(array());
        $noExpire = $inputfields->find("name=noExpire")->first;
        if($noExpire) $inputfields->remove($noExpire); // remove config inputfield
        $form = $modules->InputfieldForm;
        $form->attr("action", "./");
        $submit = $modules->InputfieldSubmit;
        $submit->attr("name", "submitmarkupcache");
        $submit->attr("value", __("Clear now"));
        $submit->showIf = "_clearCache=1";
        $form->add($inputfields);
        $form->add($submit);
        $out .= $form->render();

        // show wire caches not expired
        if($this->wire("cache")) {

            $expiringCaches = $this->getExpiringWireCaches();

            if(count($expiringCaches)){

                $form = $modules->InputfieldForm;
                $form->attr("action", "./clearwirecache");
                $form->attr("id", "clearwirecache");

                $table = $modules->MarkupAdminDataTable;
                $wireCacheID = "wirecachetable";
                $table->setID("wirecachetable");
                $table->setEncodeEntities(false);
                $table->headerRow(array(
                    "Name",
                    "Type",
                    "Expires",
                    ));

                foreach($expiringCaches as $key => $cache){
                    $expires = $cache['expires'] == "selector" ? "selector: {$cache['selector']}" : $cache['expires'];
                    $table->row(array(
                        "<label><input type='checkbox' name='caches[{$cache['name']}]'/> {$cache['name']}</label>",
                        $cache['type'],
                        $expires,
                        ));
                }

                $tableMarkup = $modules->InputfieldMarkup;
                $tableMarkup->label = __('Clear the WireCache ($cache)');
                $tableMarkup->notes = __("As found in the wire cache DB table except those that never expire.");
                $toggleBtn = "<label>";
                $toggleBtn .= "<input class='toggle_all' data-target='$wireCacheID' type='checkbox'> ";
                $toggleBtn .= __("toggle all");
                $toggleBtn .= "</label><br/>";
                $tableMarkup->value = "";
                if(count($expiringCaches) > 20) $tableMarkup->value .= $toggleBtn;
                $tableMarkup->value .= $table->render();
                $tableMarkup->value .= $toggleBtn;
                $form->add($tableMarkup);
                $submit = $modules->InputfieldSubmit;
                $submit->attr("name", "submitwirecache");
                $submit->attr("value", __("Clear now"));
                $form->add($submit);
                $out .= $form->render();

            } else {

                $form = $modules->InputfieldForm;
                $form->attr("action", "./");
                $tableMarkup = $modules->InputfieldMarkup;
                $tableMarkup->label = __('Clear the WireCache ($cache)');
                $tableMarkup->value = __("No Wire Caches found");
                $form->add($tableMarkup);
                $out .= $form->render();

            }

        }

        // other paths in cache dir, some excluded
        $cacheDirs = $this->getCacheDirFiles();
        if(count($cacheDirs)){
            $form = $modules->InputfieldForm;
            $form->attr("action", "./clearcachedirs");
            $form->attr("id", "clearcachedirs");
            $f = $modules->InputfieldMarkup;
            $f->label = __("Clear other files or directories?");
            $f->notes = __("Other files and directories as found in site/assets/cache/. They will get deleted recursively.");
            foreach($cacheDirs as $filename => $cd){
                $f->value .= "<label>";
                $f->value .= "<input type='checkbox' name='dirs[$filename]' value='1'/>";
                $f->value .= " site/assets/cache/$filename [{$cd['type']}]";
                $f->value .= "</label>";
            }
            $form->add($f);
            $submit = $modules->InputfieldSubmit;
            $submit->attr("name", "submitfiles");
            $submit->attr("value", __("Clear now"));
            $form->add($submit);
            $out .= $form->render();
        }


        return $out;

    }


    public function ___executeClearPageCache(){
        $this->wire('input')->post->clearCache = 1;
        $modules = $this->wire("modules");
        $modules->PageRender->getModuleConfigInputfields(array());
        $modules->session->redirect($this->wire("page")->url);
    }

    public function ___executeClearMarkupCache(){
        $this->wire('input')->post->clearCache = 1;
        $modules = $this->wire("modules");
        $numFiles = $modules->MarkupCache->removeAll();
        $this->message(sprintf(__("Cleared %d MarkupCache files and dirs."), $numFiles));
        $modules->session->redirect($this->wire("page")->url);
    }


    public function ___executeClearWireCache(){
        $modules = $this->wire("modules");
        $caches = $this->wire("input")->post->caches;
        if(count($caches)){
            foreach($caches as $name => $value){
                $this->wire("cache")->delete($name);
                $this->message(sprintf(__("Cleared %s WireCache."), $name));
            }
        } else if($this->wire("input")->urlSegment2){
            $name = $this->wire("input")->urlSegment2;
            $this->wire("cache")->delete($name);
            $this->message(sprintf(__("Cleared %s WireCache."), $name));
        } else {
            $this->message("No caches specified to delete");
        }

        $modules->session->redirect($this->wire("page")->url);
    }

    public function ___executeClearCacheDirs(){
        $modules = $this->wire("modules");
        $dirs = $this->wire("input")->post->dirs;
        $urlSeg = $this->wire("input")->urlSegment2;
        if(!$dirs && $urlSeg) $dirs = array("$urlSeg" => "1");

        if(count($dirs)){
            foreach($dirs as $filename => $v){
                $path = $this->wire('config')->paths->cache . "/" . $filename;
                if(!file_exists($path)) continue;
                if(is_dir($path)) {
                    wireRmdir($path, true);
                    $this->message(sprintf(__("Cleared directory %s"), $path));
                } else {
                    @unlink($path);
                    $this->message(sprintf(__("Cleared file %s"), $path));
                }
            }
        }

        $modules->session->redirect($this->wire("page")->url);
    }

    public function ___executeNavJson(array $options = array()){

        $modules = $this->wire("modules");

        $options['add'] = false;
        $options['sort'] = false;
        $options['edit'] = '{id}/';

        $numPages = $this->getCacheNumPages();

        $data["pagecache"] = array(
            "id" => "clearpagecache",
            "name" => sprintf(__("Clear Page Render Disk Cache (%d Pages)"), $numPages),
            "icon" => "bomb",
            );

        if($modules->isInstalled("MarkupCache")){
             $data["markupcache"] = array(
                "id" => "clearmarkupcache",
                "name" => __("Clear Markup Cache"),
                "icon" => "bomb"
                );
        }

        $cacheDirs = $this->getCacheDirFiles();
        if(count($cacheDirs)){
            foreach($cacheDirs as $filename => $cd){
                $data[$filename] = array(
                    "id" => "clearcachedirs/" . $filename,
                    "name" => __("Clear") . " cache/" . $filename . " ({$cd['type']})",
                    "icon" => $cd['type'] == "dir" ? "folder" : "file",
                    );
            }
        }

        $expiringCaches = $this->getExpiringWireCaches();
        if(count($expiringCaches)){
            foreach($expiringCaches as $cache){
                $data["wirecache_" . $cache['name']] = array(
                    "id" => "clearwirecache/" . $cache['name'],
                    "name" => sprintf(__("Clear %s (WireCache)"), $cache['name']),
                    "icon" => "database",
                    );
            }
        }

        $options['items'] = $data;
        return parent::___executeNavJSON($options);

    }

    protected function getCacheNumPages(){
        $path = $this->wire('config')->paths->cache . PageRender::cacheDirName . '/';
        $numPages = 0;
        $dir = null;
        try { $dir = new DirectoryIterator($path); } catch(Exception $e) { }
        if($dir) foreach($dir as $file) {
            if(!$file->isDir() || $file->isDot() || !ctype_digit($file->getFilename())) continue;
            $numPages++;
        }
        return $numPages;
    }

    protected function getCacheDirFiles(){
        $path = $this->wire('config')->paths->cache . '/';
        $numPages = 0;
        $dir = null;
        $dirs = array();
        try { $dir = new DirectoryIterator($path); } catch(Exception $e) { }
        if($dir) foreach($dir as $file) {
            if($file->isDot() || in_array($file->getFilename(), $this->exludeCacheDirFiles)) continue;
            $dirs[$file->getFilename()] = array("type" => $file->isDir() ? "dir" : "file");
        }
        return $dirs;
    }

    protected function getExpiringWireCaches(){
        $expiringCache = array();
        $wireCache = $this->wire("cache")->getInfo(false);
        if(!count($wireCache)) return $expiringCache;

        foreach($wireCache as $key => $cache){
            if($cache['expires'] == "selector"){
                $expiringCache[] = $cache;
            } else {
                 // only show caches with an expires date in future
                $date = DateTime::createFromFormat('Y-m-d H:i:s', $cache['expires']);
                if($date && $date->getTimeStamp() > time()){
                    $expiringCache[] = $cache;
                }
            }
        }

        return $expiringCache;
    }

}

