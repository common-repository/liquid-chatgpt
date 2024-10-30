jQuery(document).ready(function($){

    /* Request */
    $('form#lqdai-form').on('submit', function(e){
        $('form#lqdai-form .button').toggleClass('disabled');
        add_log( '[STARTED] - Create post, prompt: ' + $('form#lqdai-form #prompt').val() );

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lqdai_get_response',
                prompt: $('form#lqdai-form #prompt').val(),
                model: $('form#lqdai-form #model').val(),
                temperature: $('form#lqdai-form #temperature').val(),
                operation: $('form#lqdai-form #operation').val(),
                image: $("form#lqdai-form #image").is(":checked") ? true : false,
                security: $('form#lqdai-form #security').val()
            },
            success: function(data) {

                $('form#lqdai-form .button').toggleClass('disabled');

                if ( data.error === true ) {
                    add_log(data.message);
                    alert(data.message);
                } else {
                    $('.lqdai-template-content').toggleClass('result');
    
                    console.log(data);
                    add_form_data( 'insert_data', data.post);
    
                    add_log(data.message);
                    if( data.total_tokens ) {
                        add_log(data.total_tokens);
                    }
                    add_log('[DONE]');
                }
            }
        });
        e.preventDefault();
    });

    /* Insert data to result form */
    function add_form_data( action, post ) {

        var title = $('form#lqdai-form-result #title'),
            content = $('form#lqdai-form-result #content'),
            tags = $('form#lqdai-form-result #tags');

        switch ( action ) {
            case "insert_data":
                title.val(post.title);
                content.val(post.content);
                tags.val(post.tags);
                break;
        }

        if ( post.image == 'true' ) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lqdai_get_images',
                    query: tags.val(),
                },
                success: function(data) {
                    if ( data.error === true ) {
                        add_log( data.message );
                        alert( data.message );
                    }
                    add_log( 'Image Requested' );
                    $('form#lqdai-form-result .generated-images').css('display', 'block');
                    $('form#lqdai-form-result .generated-images').append( data.message );
                }
            });
        }
        
    }

    /* Result Form to Insert Post */
    $('form#lqdai-form-result').on('submit', function(e){
        $('form#lqdai-form-result .button').toggleClass('disabled');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lqdai_update_post',
                posts: {
                    post_id: $('form#lqdai-form-result #post_id').val(),
                    title: $('form#lqdai-form-result #title').val(),
                    content: $('form#lqdai-form-result #content').val(),
                    image: $('form#lqdai-form-result input[name="generated-image"]:checked').val(),
                    tags: $('form#lqdai-form-result #tags').val(),
                },
                security: $('form#lqdai-form #security').val()
            },
            success: function(data) {
                $('form#lqdai-form-result .button').toggleClass('disabled');
                $('.lqdai-template-content').toggleClass('result');
                $( ".lqdai-template" ).css('display','none');

                if ( data.error === true ) {
                    add_log(data.message);
                    alert(data.message);
                } else {
                    add_log(data.message);
                    console.log(data.message);
                    console.log(data);
                    window.location.href = data.redirect;
                }

            }
        });
        
        e.preventDefault();
    });

    // Logging actions
    function add_log( message ) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lqdai_add_log',
                log: get_log_time() + message,
            },
            success: function(data) {
                //console.log(data.message);
            }
        });
    }

    function get_log_time() {
        let date = new Date().toLocaleString();
        return '[' + date + '] - ';
    }

    
    // Add button to block editor & classic editor
    let blockLoaded = false;
    let classicLoaded = false;
    let blockLoadedInterval = setInterval(function() {
        if (document.querySelector('#editor')) {
            blockLoaded = true;
        }
        if (document.querySelector('.lqdai-action-classic')) {
            classicLoaded = true;
        }
        if ( blockLoaded ) {
            console.log('LIQUID - Block editor loaded!');
            $('.edit-post-header__toolbar').before('<div class="lqdai-action components-button edit-post-fullscreen-mode-close"><span class="dashicons dashicons-image-filter"></span> Liquid ChatGPT</div>');
            
                $( ".lqdai-action" ).click(function() {
                    $( ".lqdai-template" ).css('display','grid');
                });
               
                $( ".lqdai-template--close" ).click(function() {
                    $( ".lqdai-template" ).css('display','none');
                });

            clearInterval( blockLoadedInterval );
        }
        if ( classicLoaded ) {
            console.log('LIQUID - Classic editor loaded!');         

                $( ".lqdai-action" ).click(function() {
                    $( ".lqdai-template" ).css('display','grid');
                });
               
                $( ".lqdai-template--close" ).click(function() {
                    $( ".lqdai-template" ).css('display','none');
                });

            clearInterval( blockLoadedInterval );
        }
    }, 500);

    $( ".lqdai-recreate" ).click(function() {
        add_log("Clicked: re-create");
        $('.lqdai-template-content').toggleClass('result');
        $('form#lqdai-form-result .generated-images .generated-images-wrapper').detach();
        $('form#lqdai-form-result .generated-images').css('display', 'none');
    });
    
});