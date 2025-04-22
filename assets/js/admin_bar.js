(function($) {
    $(document).ready(function() {
        // Handle clearing all unused CSS
        $("#button-uucss-clear-all").click(function(e) {
            e.preventDefault();
            var $this = $(this);
            
            // Show loading state
            $this.text("loading...");
            
            // Make AJAX call to purge all URLs
            wp.ajax.post("uucss_purge_url", {
                clear: true,
                nonce: window.uucss.nonce,
                url: null
            }).done(function(d) {
                $this.text("removed all");
                $("#button-uucss-purge").css("display", "inline-block");
                $("#button-uucss-clear").hide();
                $(".uucss-stats__size").text("Size : 0.00KB");
            });
        });

        // Handle clearing unused CSS for specific post/page
        $("#button-uucss-clear").click(function(e) {
            e.preventDefault();
            var $this = $(this);
            
            // Show loading state
            $this.text("loading...");
            
            // Make AJAX call to purge specific URL
            wp.ajax.post("uucss_purge_url", {
                clear: true,
                url: uucss_admin_bar.post_link,
                nonce: window.uucss.nonce,
                args: {
                    post_id: uucss_admin_bar.uucss_post_id
                }
            }).done(function(d) {
                $this.text("remove");
                $this.hide();
                $("#button-uucss-purge").css("display", "inline-block");
            });
        });

        // Handle purging/optimizing unused CSS for specific post/page
        $("#button-uucss-purge").click(function(e) {
            e.preventDefault();
            var $this = $(this);
            
            // Show loading state
            $this.text("loading...");
            
            var data = {
                url: uucss_admin_bar.post_link,
                nonce: window.uucss.nonce,
                args: {
                    post_id: uucss_admin_bar.post_id
                }
            };

            // Make AJAX call to purge and optimize
            wp.ajax.post("uucss_purge_url", data)
                .done(function(d) {
                    $this.text("optimize");
                    $this.hide();
                    $("#button-uucss-clear").css("display", "inline-block");
                })
                .fail(function() {
                    $this.text("failed!");
                });
        });

        // Handle rapid load clear all functionality
        $(".rapidload-clear-all").click(function(e) {
            e.preventDefault();
            
            // Make AJAX call to purge all URLs
            wp.ajax.post("uucss_purge_url", {
                clear: true,
                nonce: window.uucss.nonce,
                url: null
            }).done(function(d) {
                window.location.reload();
            });
        });
    });
})(jQuery); 