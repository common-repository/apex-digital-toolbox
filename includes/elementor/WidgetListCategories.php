<?php

namespace ElementorCustom\Widget;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;
use WP_Term;

/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts an embeddable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
class Elementor_ListCategories_Widget extends Widget_Base {

    /**
     * Get widget categories.
     *
     * Retrieve the list of categories the oEmbed widget belongs to.
     *
     * @return array Widget categories.
     * @since 1.0.0
     * @access public
     *
     */
    public function get_categories(): array {
        return [ 'general' ];
    }

    /**
     * Get widget icon.
     *
     * Retrieve oEmbed widget icon.
     *
     * @return string Widget icon.
     * @since 1.0.0
     * @access public
     *
     */
    public function get_icon(): string {
        return 'fa fa-bars';
    }

    /**
     * Get widget name.
     *
     * Retrieve oEmbed widget name.
     *
     * @return string Widget name.
     * @since 1.0.0
     * @access public
     *
     */
    public function get_name(): string {
        return 'list_categories';
    }

    /**
     * Get widget title.
     *
     * Retrieve oEmbed widget title.
     *
     * @return string Widget title.
     * @since 1.0.0
     * @access public
     *
     */
    public function get_title(): string {
        return 'List Categories';
    }

    /**
     * @param int $termId
     * @param boolean $includeDetails
     *
     * @return WP_Term[]
     */
    private function getCategories( int $termId = 0, bool $includeDetails = false ): array {
        $args       = [
            'taxonomy' => 'product_cat',
            'orderby'  => 'name',
            'parent'   => $termId
        ];
        $terms      = get_terms( $args );
        $categories = [];
        foreach ( $terms as $term ) {
            $categories[ $term->term_id ] = ( $includeDetails ? $term : $term->name );
        }

        return $categories;
    }

    /**
     * Register oEmbed widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function _register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Content',
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'parent_category',
            [
                'label'       => 'Parent Category',
                'type'        => Controls_Manager::SELECT,
                'description' => 'Select parent category to show child categories',
                'options'     => [ 'Show All' ] + $this->getCategories()
            ]
        );

        $this->add_control( 'show_parent_link', [
            'label'        => 'Show parent link',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Show',
            'label_off'    => 'Hide',
            'return_value' => '1',
            'description'  =>
                'Include the parent link above the child links'
        ] );

        $this->end_controls_section();

        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Style',
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'link_typography',
                'selector' => '{{WRAPPER}} a',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'label'    => 'Parent Typography',
                'name'     => 'parent_link_typography',
                'selector' => '{{WRAPPER}} a.category-parent-link',
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label'     => esc_html__( 'Alignment', 'elementor' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => esc_html__( 'Left', 'elementor' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'elementor' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => esc_html__( 'Right', 'elementor' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'link_padding',
            [
                'label'      => esc_html__( 'Padding', 'elementor-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'border_radius',
            [
                'label'      => esc_html__( 'Border Radius', 'elementor-pro' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'border_width',
            [
                'label'     => esc_html__( 'Border Width', 'elementor-pro' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'border: {{SIZE}}{{UNIT}} solid;',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_link_style' );

        $this->start_controls_tab(
            'tab_link_normal',
            [
                'label' => __( 'Normal', 'elementor' ),
            ]
        );

        $this->add_control(
            'link_text_color',
            [
                'label'     => __( 'Link Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_text_background_color',
            [
                'label'     => __( 'Link Background Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'parent_link_text_color',
            [
                'label'     => __( 'Parent Link Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a.category-parent-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_color',
            [
                'label'     => __( 'Border Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_link_hover',
            [
                'label' => __( 'Hover', 'elementor' ),
            ]
        );

        $this->add_control(
            'link_hover_color',
            [
                'label'     => __( 'Link Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a:hover, {{WRAPPER}} .elementor-category-list-container a:focus' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_hover_background_color',
            [
                'label'     => __( 'Link Background Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a:hover, {{WRAPPER}} .elementor-category-list-container a:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'parent_link_hover_color',
            [
                'label'     => __( 'Parent Link Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a.category-parent-link:hover, {{WRAPPER}} .elementor-category-list-container a.category-parent-link:focus' => 'color: {{VALUE}};',
                ],
            ]
        );


        $this->add_control(
            'border_hover_color',
            [
                'label'     => __( 'Border Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_link_active',
            [
                'label' => __( 'Active', 'elementor' ),
            ]
        );

        $this->add_control(
            'link_active_color',
            [
                'label'     => __( 'Link Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a.current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_active_background_color',
            [
                'label'     => __( 'Link Background Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a.current' => 'background-color: {{VALUE}};',
                ],
            ]
        );


        $this->add_control(
            'border_active_color',
            [
                'label'     => __( 'Border Color', 'elementor' ),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container a.current' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'container_vertical_padding',
            [
                'label'     => 'Container Vertical Padding',
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container > ul' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}}',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'link_space_between',
            [
                'label'     => 'Space Between',
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container li' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                ],
            ]
        );

        $this->add_control(
            'heading_columns',
            [
                'label'     => 'Columns',
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label'     => 'Total',
                'type'      => Controls_Manager::SELECT,
                'default'   => '1',
                'options'   => [
                    '1'  => 'One',
                    '2'  => 'Two',
                    '3'  => 'Three',
                    '4'  => 'Four',
                    '5'  => 'Five',
                    '6'  => 'Six',
                    '-1' => 'Unlimited - wrap',
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container > ul' => 'columns: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'column_distance_between',
            [
                'label'     => 'Distance Between',
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container > ul' => 'column-gap: {{SIZE}}{{UNIT}}',
                ],
            ]
        );

        $this->add_control(
            'column_divider_width',
            [
                'label'     => 'Divider Width',
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max' => 10,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container > ul' => 'column-rule-style: solid; column-rule-width: {{SIZE}}{{UNIT}}',
                ],
            ]
        );

        $this->add_control(
            'column_divider_color',
            [
                'label'     => 'Color',
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-category-list-container > ul' => 'column-rule-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

    }

    /**
     * Render oEmbed widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function render() {
        $settings          = $this->get_settings_for_display();
        $parent_category   = intval( $settings['parent_category'] );
        $show_parent_link  = intval( $settings['show_parent_link'] );
        $parentLinkDisplay = $parent_category > 0 && $show_parent_link === 1;
        $columns           = intval( $settings['columns'] );
        $parent_term       = get_term( $parent_category, 'product_cat' );
        $terms             = $this->getCategories( $parent_category, true );
        ?>
        <style>
            .elementor-element.elementor-element-<?php echo $this->get_id(); ?> .elementor-category-list-container ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .elementor-element.elementor-element-<?php echo $this->get_id(); ?> .elementor-category-list-container ul:not([data-columns="-1"]) li {
                display: inline-block;
                width: 100%;
            }

            .elementor-element.elementor-element-<?php echo $this->get_id(); ?> .elementor-category-list-container ul a {
                display: block;
            }

            .elementor-element.elementor-element-<?php echo $this->get_id(); ?> .elementor-category-list-container ul[data-columns="-1"] {
                display: flex;
                flex-wrap: wrap;
            }
        </style>
        <div class="elementor-category-list-container">
        <?php
        echo( $parentLinkDisplay ? '<a href="' . get_term_link( $parent_term ) . '" class="category-parent-link">' . $parent_term->name . '</a>' : '' );
        echo '<ul data-columns="' . $columns . '">';
        if ( count( $terms ) ) {
            foreach ( $terms as $term ) {
                echo ' <li class="term-' . $term->term_id . '"><a href = "' . get_term_link( $term ) . '"' . ( get_queried_object_id() == $term->term_id ? ' class="current"' : '' ) . '> ' . $term->name . '</a></li> ';
            }
        }
        echo '
            </ul>
        </div>';
    }
}

add_action('elementor/widgets/register', function($widgets_manager) {
    $widgets_manager->register( new Elementor_ListCategories_Widget() );
} );