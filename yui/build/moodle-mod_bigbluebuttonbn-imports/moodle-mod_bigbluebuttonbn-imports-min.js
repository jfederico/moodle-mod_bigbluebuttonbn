YUI.add("moodle-mod_bigbluebuttonbn-imports",function(t,n){M.mod_bigbluebuttonbn=M.mod_bigbluebuttonbn||{},M.mod_bigbluebuttonbn.imports={init:function(o){t.one("#menuimport_recording_links_select").on("change",function(){var n="?bn="+o.bn+"&tc="+this.get("value");t.config.win.location=M.cfg.wwwroot+"/mod/bigbluebuttonbn/import_view.php"+n})}}},"@VERSION@",{requires:["base","node"]});