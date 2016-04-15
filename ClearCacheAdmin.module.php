<?php

/**
 * Clear Cache Admin
 * ================================================================================
 */


class ClearCacheAdmin extends Process{

    /**
     * Return information about this module (required)
     *
     */
    public static function getModuleInfo() {
        return array(
            'title' => 'Clear Cache Admin',
            'summary' => 'Tool that helps you clear page cache.',
            'version' => 1,
            'author' => 'Soma',
            'icon' => 'gear',
            'page' => array(
                'parent' => "setup",
                'name' => "clear-cache-admin",
                'title' => "Cache Admin",
                ),
            'useNavJSON' => true,
            'requires' => array('ProcessWire>=2.7.0'),
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
        $form->add($inputfields);
        $form->add($submit);
        $out .= $form->render();

        // clear markup cache form, we get this from the module's config inputfields for convenience
        $inputfields = $modules->MarkupCache->getModuleConfigInputfields(array());
        $inputfields->remove("noExpire"); // remove config inputfield
        $form = $modules->InputfieldForm;
        $form->attr("action", "./");
        $submit = $modules->InputfieldSubmit;
        $form->add($inputfields);
        $form->add($submit);
        $out .= $form->render();

        // show wire caches not expired
        if($this->wire("cache")) {

            $expiringCaches = $this->getExpiringWireCaches();

            if(count($expiringCaches)){

                $form = $modules->InputfieldForm;
                $form->attr("action", "./clearwirecache");

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
                    $table->row(array(
                        "<label><input type='checkbox' name='caches[{$cache['name']}]'/> {$cache['name']}</label>",
                        $cache['type'],
                        $cache['expires'],
                        ));
                }

                $tableMarkup = $modules->InputfieldMarkup;
                $tableMarkup->label = __('Delete WireCache ($cache)');
                $toggleBtn = "<label><input class='toggle_all' data-target='$wireCacheID' type='checkbox'> " . __("toggle all") . "</label><br/>";
                $tableMarkup->value = "";
                if(count($expiringCaches) > 20) $tableMarkup->value .= $toggleBtn;
                $tableMarkup->value .= $table->render();
                $tableMarkup->value .= $toggleBtn;
                $form->add($tableMarkup);
                $submit = $modules->InputfieldSubmit;
                $form->add($submit);
                $out .= $form->render();

            } else {

                $form = $modules->InputfieldForm;
                $form->attr("action", "./");
                $tableMarkup = $modules->InputfieldMarkup;
                $tableMarkup->label = __('Delete WireCache ($cache)');
                $tableMarkup->value = __("No Wire Caches found");
                $form->add($tableMarkup);
                $out .= $form->render();
            }

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


    public function ___executeNavJson(array $options = array()){

        $modules = $this->wire("modules");

        $options['add'] = false;
        $options['sort'] = false;
        $options['edit'] = '{id}/';

        $numPages = $this->getCacheFiles();

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

    protected function getCacheFiles(){
        $path = $this->wire('config')->paths->cache . PageRender::cacheDirName . '/';
        $numPages = 0;
        $dir = null;
        try { $dir = new \DirectoryIterator($path); } catch(\Exception $e) { }
        if($dir) foreach($dir as $file) {
            if(!$file->isDir() || $file->isDot() || !ctype_digit($file->getFilename())) continue;
            $numPages++;
        }
        return $numPages;
    }

    protected function getExpiringWireCaches(){
        $wireCache = $this->wire("cache")->getInfo(false);
        $expiringCache = array();
        foreach($wireCache as $key => $cache){
            // only show caches with an expires date in future
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $cache['expires']);
            if($date->getTimeStamp() > time()){
                $expiringCache[] = $cache;
            }
        }
         return $expiringCache;
    }

}

