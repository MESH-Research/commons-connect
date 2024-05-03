/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from "@wordpress/block-editor";

import { useState } from "react";

const CustomDateRange = () => {
	const [ className, setClassName ] = useState( '' );
	const handleChange = (event) => {
		setClassName(event.target.value);
		console.log(event.target.value);
	  };
	return (
		<div className="ccs-row ccs-date-ranges">
			<label>
				<span>End Date</span>
				<br />
				<input
					type="text"
					name="customEndDate"
					value={ className }
					onChange={ ( v ) => handleChange( v )}
				/>
			</label>
		</div>
	);
}

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {Element} Element to render.
 */
export default function save() {
	return (
		<CustomDateRange {...useBlockProps.save()} />
	);
}
