<?php


/**
 * Class_Postartify_CPT
 * Register The Custom Post Type
 */

class Postartify_CPT {

    public function register_cpt(){
        register_post_type(
            'portfolio',
            array(
				'labels'      => array(
					'name'          => __( 'Portfolio' ),
					'singular_name' => __( 'Portfolio' ),
					'add_new'       => __( 'Add New' ),
					'add_new_item'  => __( 'Add New Portfolio' ),
				),
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => array(
					'slug'       => 'portfolio',
					'with_front' => false,
				),
				'supports'    => array( 'title', 'editor', 'thumbnail', 'comments' ),
				'menu_icon'   => 'dashicons-portfolio',
			)
        );

    }

}