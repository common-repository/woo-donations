/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

import ServerSideRender from "@wordpress/server-side-render";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */

export default function Edit( { attributes, setAttributes } ) {
    const { productId } = attributes;


    return (
        <div { ...useBlockProps() }>
            <InspectorControls>
                <PanelBody title={ __( 'Settings', 'woo-donations-pro' ) }>
                    <TextControl
                        label={ __(
                            'Product ID',
                            'woo-donations-pro'
                        ) }
                        value={ productId || '' }
                        onChange={ ( value ) =>
                            setAttributes( { productId: value } )
                        }
                    />
                </PanelBody>
            </InspectorControls>
                <ServerSideRender 
                    block="woo-donations-block/woo-donations"
                    attributes={attributes}
                />
        </div>
    );
}