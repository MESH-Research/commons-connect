<?php
/**
 * REST controller for plugin search.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;

/**
 * REST controller for search
 */
class SearchController extends WP_REST_Controller {
    protected $namespace;
    protected $resource_name;
    protected $schema;

    public function __construct() {
        $this->namespace     = CC_CLIENT_REST_NAMESPACE;
        $this->resource_name = 'search';
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->resource_name,
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_options' ],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => 'POST',
                    'callback'            => [ $this, 'update_options' ],
                    'permission_callback' => '__return_true',
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    public function get_items_permission_check( WP_REST_Request $request ) : bool {
        return current_user_can( 'manage_options' );
    }

    public function update_options_permission_check( WP_REST_Request $request ) : bool {
        return current_user_can( 'manage_options' );
    }

    public function get_options( WP_REST_Request $request ) : string {
        // return "Hello World";
		return '{
			"total": 385,
			"page": 0,
			"per_page": 0,
			"request_id": "",
			"hits": [
				{
					"_internal_id": "1017377",
					"title": "Maureen Fitzpatrick",
					"description": "I am a professor of English at Johnson County Community College. I have been teaching courses in writing for new media and video games for 15 years.",
					"owner": {
						"name": "Maureen Fitzpatrick",
						"username": "fitzreen",
						"url": "https://commons-wordpress.lndo.site/members/fitzreen",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/fitzreen",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/fitzreen",
						"https://ajs.commons-wordpress.lndo.site/members/fitzreen",
						"https://aseees.commons-wordpress.lndo.site/members/fitzreen",
						"https://caa.commons-wordpress.lndo.site/members/fitzreen",
						"https://up.commons-wordpress.lndo.site/members/fitzreen",
						"https://commons.msu.edu/members/fitzreen",
						"https://arlisna.commons-wordpress.lndo.site/members/fitzreen",
						"https://sah.commons-wordpress.lndo.site/members/fitzreen",
						"https://commonshub.org/members/fitzreen",
						"https://socsci.commons-wordpress.lndo.site/members/fitzreen",
						"https://stem.commons-wordpress.lndo.site/members/fitzreen",
						"https://hastac.commons-wordpress.lndo.site/members/fitzreen"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/a31c9c61d47d5634965e8b1ea433d72c?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2019-03-01",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1027539",
					"title": "Caroline Fitzpatrick",
					"owner": {
						"name": "Caroline Fitzpatrick",
						"username": "caroline37",
						"url": "https://commons-wordpress.lndo.site/members/caroline37",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/caroline37",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/caroline37",
						"https://ajs.commons-wordpress.lndo.site/members/caroline37",
						"https://aseees.commons-wordpress.lndo.site/members/caroline37",
						"https://caa.commons-wordpress.lndo.site/members/caroline37",
						"https://up.commons-wordpress.lndo.site/members/caroline37",
						"https://commons.msu.edu/members/caroline37",
						"https://arlisna.commons-wordpress.lndo.site/members/caroline37",
						"https://sah.commons-wordpress.lndo.site/members/caroline37",
						"https://commonshub.org/members/caroline37",
						"https://socsci.commons-wordpress.lndo.site/members/caroline37",
						"https://stem.commons-wordpress.lndo.site/members/caroline37",
						"https://hastac.commons-wordpress.lndo.site/members/caroline37"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/6b3fa25b742f823d1e1ec3b21a93485f?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2021-03-01",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1024746",
					"title": "Jessica FitzPatrick",
					"owner": {
						"name": "Jessica FitzPatrick",
						"username": "jfitz",
						"url": "https://commons-wordpress.lndo.site/members/jfitz",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/jfitz",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/jfitz",
						"https://ajs.commons-wordpress.lndo.site/members/jfitz",
						"https://aseees.commons-wordpress.lndo.site/members/jfitz",
						"https://caa.commons-wordpress.lndo.site/members/jfitz",
						"https://up.commons-wordpress.lndo.site/members/jfitz",
						"https://commons.msu.edu/members/jfitz",
						"https://arlisna.commons-wordpress.lndo.site/members/jfitz",
						"https://sah.commons-wordpress.lndo.site/members/jfitz",
						"https://commonshub.org/members/jfitz",
						"https://socsci.commons-wordpress.lndo.site/members/jfitz",
						"https://stem.commons-wordpress.lndo.site/members/jfitz",
						"https://hastac.commons-wordpress.lndo.site/members/jfitz"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/04b424e3185a10b901f1774236a92b3c?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2020-08-13",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1042277",
					"title": "Maryanne Kowaleski",
					"description": "Joseph Fitzpatrick Distinguished Professor of History, Fordham University.  See http://www.fordham.edu/academics/programs_at_fordham_/history_department/faculty/maryanne_kowaleski/",
					"owner": {
						"name": "Maryanne Kowaleski",
						"username": "mkowaleski",
						"url": "https://commons-wordpress.lndo.site/members/mkowaleski",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/mkowaleski",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/mkowaleski",
						"https://ajs.commons-wordpress.lndo.site/members/mkowaleski",
						"https://aseees.commons-wordpress.lndo.site/members/mkowaleski",
						"https://caa.commons-wordpress.lndo.site/members/mkowaleski",
						"https://up.commons-wordpress.lndo.site/members/mkowaleski",
						"https://commons.msu.edu/members/mkowaleski",
						"https://arlisna.commons-wordpress.lndo.site/members/mkowaleski",
						"https://sah.commons-wordpress.lndo.site/members/mkowaleski",
						"https://commonshub.org/members/mkowaleski",
						"https://socsci.commons-wordpress.lndo.site/members/mkowaleski",
						"https://stem.commons-wordpress.lndo.site/members/mkowaleski",
						"https://hastac.commons-wordpress.lndo.site/members/mkowaleski"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/078312be00f3cfc235cec29a9d7d9425?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2013-09-25",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1035901",
					"title": "Coeli Fitzpatrick",
					"description": "Professor of Philosophy, Frederik Meijer Honors College. Work on intellectual history of Averroes, Edward Said, and Islamophobia.",
					"owner": {
						"name": "Coeli Fitzpatrick",
						"username": "coelifitz",
						"url": "https://commons-wordpress.lndo.site/members/coelifitz",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/coelifitz",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/coelifitz",
						"https://ajs.commons-wordpress.lndo.site/members/coelifitz",
						"https://aseees.commons-wordpress.lndo.site/members/coelifitz",
						"https://caa.commons-wordpress.lndo.site/members/coelifitz",
						"https://up.commons-wordpress.lndo.site/members/coelifitz",
						"https://commons.msu.edu/members/coelifitz",
						"https://arlisna.commons-wordpress.lndo.site/members/coelifitz",
						"https://sah.commons-wordpress.lndo.site/members/coelifitz",
						"https://commonshub.org/members/coelifitz",
						"https://socsci.commons-wordpress.lndo.site/members/coelifitz",
						"https://stem.commons-wordpress.lndo.site/members/coelifitz",
						"https://hastac.commons-wordpress.lndo.site/members/coelifitz"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/3a5a6d52407f68749c1905b4a15088f4?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2018-03-12",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1032707",
					"title": "Jacob Fitzpatrick",
					"owner": {
						"name": "Jacob Fitzpatrick",
						"username": "jfitzpatrick",
						"url": "https://commons-wordpress.lndo.site/members/jfitzpatrick",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/jfitzpatrick",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://ajs.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://aseees.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://caa.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://up.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://commons.msu.edu/members/jfitzpatrick",
						"https://arlisna.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://sah.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://commonshub.org/members/jfitzpatrick",
						"https://socsci.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://stem.commons-wordpress.lndo.site/members/jfitzpatrick",
						"https://hastac.commons-wordpress.lndo.site/members/jfitzpatrick"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/d171afd570b090f16ba753823fca35ab?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2022-06-24",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1031454",
					"title": "Sheila Fitzpatrick",
					"owner": {
						"name": "Sheila Fitzpatrick",
						"username": "sheilaf",
						"url": "https://commons-wordpress.lndo.site/members/sheilaf",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/sheilaf",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/sheilaf",
						"https://ajs.commons-wordpress.lndo.site/members/sheilaf",
						"https://aseees.commons-wordpress.lndo.site/members/sheilaf",
						"https://caa.commons-wordpress.lndo.site/members/sheilaf",
						"https://up.commons-wordpress.lndo.site/members/sheilaf",
						"https://commons.msu.edu/members/sheilaf",
						"https://arlisna.commons-wordpress.lndo.site/members/sheilaf",
						"https://sah.commons-wordpress.lndo.site/members/sheilaf",
						"https://commonshub.org/members/sheilaf",
						"https://socsci.commons-wordpress.lndo.site/members/sheilaf",
						"https://stem.commons-wordpress.lndo.site/members/sheilaf",
						"https://hastac.commons-wordpress.lndo.site/members/sheilaf"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/6c65b171f988cc6214681321345b0d7a?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2022-02-03",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "847",
					"title": "Video: Academic Careers and New Forms of Scholarly Communication",
					"description": "An excerpt from a filmed conversation with Kathleen Fitzpatrick, director of scholarly communication at the MLA:",
					"owner": {
						"name": "Susana Aho",
						"username": "saho",
						"url": "https://scholcomm.mla.commons-wordpress.lndo.site/members/saho",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Susana Aho",
							"username": "saho",
							"url": "https://scholcomm.mla.commons-wordpress.lndo.site/members/saho",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://scholcomm.mla.commons-wordpress.lndo.site/digital-publications/video-academic-careers-and-new-forms-of-scholarly-communication/",
					"thumbnail_url": "https://scholcomm.mla.commons-wordpress.lndo.site/wp-content/blogs.dir/2/files/sites/2/2016/03/video-academic-careers-and-new-f.jpg",
					"content": "An excerpt from a filmed conversation with Kathleen Fitzpatrick, director of scholarly communication at the MLA:\r\n\r\n[embed]https://www.youtube.com/watch?v=iqqCnPkLgn4&feature=youtu.be[/embed]",
					"publication_date": "2016-03-22",
					"modified_date": "2017-05-26",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "60",
					"title": "Kathleen Fitzpatrick",
					"description": "Kathleen Fitzpatrick is Director of Digital Humanities and Professor of English at Michigan State University, where she also directs MESH, a research and development unit focused on the future of scholarly communication. She is project director of Humanities Commons, an open-access, open-source network serving more than 30,000 scholars and practitioners across the humanities and around the world, and she is author of Generous Thinking: A Radical Approach to Saving the University (Johns Hopkins University Press, 2019), Planned Obsolescence:  Publishing, Technology, and the Future of the Academy (NYU Press, 2011), and The Anxiety of Obsolescence: The American Novel in the Age of Television (Vanderbilt University Press, 2006). She is president of the board of directors of the Educopia Institute, and she served as president of the Association for Computers and the Humanities from 2020 to 2022.\r\n\r\nFind me on hcommons.social.",
					"owner": {
						"name": "Kathleen Fitzpatrick",
						"username": "kfitz",
						"url": "https://commons-wordpress.lndo.site/members/kfitz",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/kfitz",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/kfitz",
						"https://ajs.commons-wordpress.lndo.site/members/kfitz",
						"https://aseees.commons-wordpress.lndo.site/members/kfitz",
						"https://caa.commons-wordpress.lndo.site/members/kfitz",
						"https://up.commons-wordpress.lndo.site/members/kfitz",
						"https://commons.msu.edu/members/kfitz",
						"https://arlisna.commons-wordpress.lndo.site/members/kfitz",
						"https://sah.commons-wordpress.lndo.site/members/kfitz",
						"https://commonshub.org/members/kfitz",
						"https://socsci.commons-wordpress.lndo.site/members/kfitz",
						"https://stem.commons-wordpress.lndo.site/members/kfitz",
						"https://hastac.commons-wordpress.lndo.site/members/kfitz"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/4e25ab8fd24223d9bd5b94295816ea32?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2012-12-10",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1029457",
					"title": "Michael Fitzpatrick",
					"owner": {
						"name": "Michael Fitzpatrick",
						"username": "michaelfitzpatrick",
						"url": "https://commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/michaelfitzpatrick",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://ajs.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://aseees.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://caa.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://up.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://commons.msu.edu/members/michaelfitzpatrick",
						"https://arlisna.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://sah.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://commonshub.org/members/michaelfitzpatrick",
						"https://socsci.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://stem.commons-wordpress.lndo.site/members/michaelfitzpatrick",
						"https://hastac.commons-wordpress.lndo.site/members/michaelfitzpatrick"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/f0789b820241d2cf0580d956650ff43f?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2021-08-04",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1028236",
					"title": "Pamela fitzpatrick",
					"owner": {
						"name": "Pamela fitzpatrick",
						"username": "nicaraguaphoto85",
						"url": "https://commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/nicaraguaphoto85",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://ajs.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://aseees.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://caa.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://up.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://commons.msu.edu/members/nicaraguaphoto85",
						"https://arlisna.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://sah.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://commonshub.org/members/nicaraguaphoto85",
						"https://socsci.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://stem.commons-wordpress.lndo.site/members/nicaraguaphoto85",
						"https://hastac.commons-wordpress.lndo.site/members/nicaraguaphoto85"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/4e4cc2f4198174820bb1bade64ac139b?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2021-04-27",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1046953",
					"title": "Terry Fitzpatrick",
					"description": "Blogger in Phoenix.",
					"owner": {
						"name": "Terry Fitzpatrick",
						"username": "tfitzaz",
						"url": "https://commons-wordpress.lndo.site/members/tfitzaz",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/tfitzaz",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/tfitzaz",
						"https://ajs.commons-wordpress.lndo.site/members/tfitzaz",
						"https://aseees.commons-wordpress.lndo.site/members/tfitzaz",
						"https://caa.commons-wordpress.lndo.site/members/tfitzaz",
						"https://up.commons-wordpress.lndo.site/members/tfitzaz",
						"https://commons.msu.edu/members/tfitzaz",
						"https://arlisna.commons-wordpress.lndo.site/members/tfitzaz",
						"https://sah.commons-wordpress.lndo.site/members/tfitzaz",
						"https://commonshub.org/members/tfitzaz",
						"https://socsci.commons-wordpress.lndo.site/members/tfitzaz",
						"https://stem.commons-wordpress.lndo.site/members/tfitzaz",
						"https://hastac.commons-wordpress.lndo.site/members/tfitzaz"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/6454b874d4d786827b809e03bf98c149?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2011-02-12",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "278",
					"title": "Fitzpatrick Speaks about Open Access",
					"description": "In a statement at the National Academy of Sciences meeting on public access to federally supported research and development publications, Kathleen Fitzpatrick, the MLA’s director of scholarly communication, discussed the changing role of professional societies in the Internet era. Citing the ability to disseminate one’s work through a platform like MLA Commons, Fitzpatrick posited that [&hellip;]",
					"owner": {
						"name": "Anna Chang",
						"username": "annachang",
						"url": "https://news.mla.commons-wordpress.lndo.site/members/annachang",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Anna Chang",
							"username": "annachang",
							"url": "https://news.mla.commons-wordpress.lndo.site/members/annachang",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://news.mla.commons-wordpress.lndo.site/2013/05/15/fitzpatrick-speaks-about-open-access/",
					"content": "In a statement at the National Academy of Sciences meeting on public access to federally supported research and development publications, Kathleen Fitzpatrick, the MLA’s director of scholarly communication, discussed the changing role of professional societies in the Internet era. Citing the ability to disseminate one’s work through a platform like MLA Commons, Fitzpatrick posited that “the value of joining a scholarly society in the age of open, public Web-based communication may be in participation.” Read the full text of her statement on the office of scholarly communication blog.\r\n\r\n&nbsp;",
					"publication_date": "2013-05-15",
					"modified_date": "2013-06-19",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "1039092",
					"title": "L. Kelly Fitzpatrick",
					"description": "archives student / blog person / digital collections + curation / hometown: internet",
					"owner": {
						"name": "L. Kelly Fitzpatrick",
						"username": "lkfitz",
						"url": "https://commons-wordpress.lndo.site/members/lkfitz",
						"role": "user",
						"network_node": "hc"
					},
					"primary_url": "https://commons-wordpress.lndo.site/members/lkfitz",
					"other_urls": [
						"https://mla.commons-wordpress.lndo.site/members/lkfitz",
						"https://ajs.commons-wordpress.lndo.site/members/lkfitz",
						"https://aseees.commons-wordpress.lndo.site/members/lkfitz",
						"https://caa.commons-wordpress.lndo.site/members/lkfitz",
						"https://up.commons-wordpress.lndo.site/members/lkfitz",
						"https://commons.msu.edu/members/lkfitz",
						"https://arlisna.commons-wordpress.lndo.site/members/lkfitz",
						"https://sah.commons-wordpress.lndo.site/members/lkfitz",
						"https://commonshub.org/members/lkfitz",
						"https://socsci.commons-wordpress.lndo.site/members/lkfitz",
						"https://stem.commons-wordpress.lndo.site/members/lkfitz",
						"https://hastac.commons-wordpress.lndo.site/members/lkfitz"
					],
					"thumbnail_url": "//www.gravatar.com/avatar/6d94170e5dff8f49b73eeea541974e47?s=96&#038;r=g&#038;d=identicon",
					"publication_date": "2015-03-31",
					"content_type": "user",
					"network_node": "hc"
				},
				{
					"_internal_id": "1762",
					"title": "MLA Staff Members Lobby for the Humanities on Capitol Hill",
					"description": "As part of its mission, the MLA regularly advocates for federal funding for the humanities in cooperation with other leaders in higher education. On 9 March 2017, the MLA’s director of programs, Dennis Looney, attended a series of meetings on Capitol Hill organized by the Coalition of International Education (CIE). Looney joined a group of [&hellip;]",
					"owner": {
						"name": "Stacy Hartman",
						"username": "stacy_hartman",
						"url": "https://news.mla.commons-wordpress.lndo.site/members/stacy_hartman",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Stacy Hartman",
							"username": "stacy_hartman",
							"url": "https://news.mla.commons-wordpress.lndo.site/members/stacy_hartman",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://news.mla.commons-wordpress.lndo.site/2017/04/28/mla-staff-members-lobby-for-the-humanities-on-capitol-hill/",
					"content": "[caption id=\"attachment_1760\" align=\"alignleft\" width=\"300\"] From left to right: Hunter O\'Hanian, the College Art Association\'s executive director; Kathleen Fitzpatrick, the MLA\'s associate executive director; and Morgan Brand, Senator Charles Schumer\'s legislative aide. Photo: Nicholas Obourn[/caption]\r\n\r\nAs part of its mission, the MLA regularly advocates for federal funding for the humanities in cooperation with other leaders in higher education. On 9 March 2017, the MLA’s director of programs, Dennis Looney, attended a series of meetings on Capitol Hill organized by the Coalition of International Education (CIE). Looney joined a group of international educators from institutions of higher education in New York to meet with staffers from the offices of Charles Schumer, Kirsten Gillibrand, and Adriano Espaillat, as well as staffers from the office of Lamar Alexander, chair of the Senate Committee on Health, Education, Labor and Pensions. These meetings stressed the importance of the Department of Education’s international and foreign language education programs, and CIE specifically asked that the 2017 funding for Title VI and Fulbright-Hays be increased from $72 million to $78.5 million in 2018.\r\n\r\nOn 14 March 2017, Kathleen Fitzpatrick, the MLA’s associate executive director, took part in advocacy meetings organized by the National Humanities Alliance (NHA) as part of Humanities Advocacy Day. With colleagues from the College Art Association, Fitzpatrick met with staffers from the offices of Charles Schumer, Kirsten Gillibrand, Grace Meng, Peter King, Eliot Engel, and Jerrold Nadler to thank them for their past support of the National Endowment for the Humanities (NEH) and other related humanities programs and to encourage them to continue this support. The NHA is asking Congress to provide at least $155 million in 2018 for the NEH and to forcefully reject any efforts to eliminate the agency. Fitzpatrick also drew each staffer’s attention to the importance of the NEH-funded project Humanities Commons, which makes the work that humanities scholars do available in classrooms nationwide.",
					"publication_date": "2017-04-28",
					"modified_date": "2017-04-28",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "80",
					"title": "Happy (belated) birthday to Humanities Commons",
					"description": "Happy to add my little site to the Humanities Commons community. Read Kathleen Fitzpatrick&#8217;s account of Humanities Commons&#8217; first year here.",
					"owner": {
						"name": "Tim Watson",
						"username": "tim_watson",
						"url": "https://timwatson.commons-wordpress.lndo.site/members/tim_watson",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Tim Watson",
							"username": "tim_watson",
							"url": "https://timwatson.commons-wordpress.lndo.site/members/tim_watson",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://timwatson.commons-wordpress.lndo.site/2017/12/22/happy-belated-birthday-to-humanities-commons/",
					"content": "Happy to add my little site to the Humanities Commons community. Read Kathleen Fitzpatrick\'s account of Humanities Commons\' first year here.",
					"publication_date": "2017-12-22",
					"modified_date": "2017-12-22",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "45",
					"title": "About the Committee",
					"description": "Current Committee Staff liaisons: Kathleen Fitzpatrick and James Hatch (newvariorum@mla.org) Heidi Brayman Hackel, 2012–16; 2015–16 (Ch.) Alan B. Farmer, 2015–19 Alan Galey, 2012–16 Richard A. J. Knowles, 1992– (ex officio) Joseph M. Ortiz, 2015–19 Eric Rasmussen, 2014– (ex officio) Sarah Werner, 2012–16 Paul Werstine, 1997– (ex officio)",
					"owner": {
						"name": "Nicky Agate",
						"username": "terrainsvagues",
						"url": "https://nvs.mla.commons-wordpress.lndo.site/members/terrainsvagues",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Nicky Agate",
							"username": "terrainsvagues",
							"url": "https://nvs.mla.commons-wordpress.lndo.site/members/terrainsvagues",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://nvs.mla.commons-wordpress.lndo.site/about-the-committee/",
					"content": "Current Committee\r\nStaff liaisons: Kathleen Fitzpatrick and James Hatch (newvariorum@mla.org)\r\n\r\nHeidi Brayman Hackel, 2012–16; 2015–16 (Ch.)\r\nAlan B. Farmer, 2015–19\r\nAlan Galey, 2012–16\r\nRichard A. J. Knowles, 1992– (ex officio)\r\nJoseph M. Ortiz, 2015–19\r\nEric Rasmussen, 2014– (ex officio)\r\nSarah Werner, 2012–16\r\nPaul Werstine, 1997– (ex officio)",
					"publication_date": "2015-09-22",
					"modified_date": "2015-09-24",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "5898",
					"title": "Downtime and the Muscle of Attention",
					"description": "Via Kathleen Fitzpatrick Engage. Disengage. Repeat. Ferris Jabr &#8220;You need more downtime than you think&#8221; Moments of respite may even be necessary to keep one’s moral compass in working order and maintain a sense of self. [our emphasis] https://www.salon.com/2013/10/16/your_brain_needs_more_downtime_than_it_thinks_partner/ Feel free to putter (North America) or potter about (England). And so for day 2297 28.03.2013",
					"owner": {
						"name": "Francois Lachance",
						"username": "francoislachance",
						"url": "https://berneval.commons-wordpress.lndo.site/members/francoislachance",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Francois Lachance",
							"username": "francoislachance",
							"url": "https://berneval.commons-wordpress.lndo.site/members/francoislachance",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://berneval.commons-wordpress.lndo.site/2013/03/28/downtime-and-the-muscle-of-attention/",
					"content": "Via Kathleen Fitzpatrick Engage. Disengage. Repeat. \r\n\r\nFerris Jabr\r\n\"You need more downtime than you think\"\r\n\r\n\r\n\r\nMoments of respite may even be necessary to keep one’s moral compass in working order and maintain a sense of self. [our emphasis]\r\n\r\n\r\nhttps://www.salon.com/2013/10/16/your_brain_needs_more_downtime_than_it_thinks_partner/ \r\n\r\nFeel free to putter (North America) or potter about (England). \r\n\r\nAnd so for day 2297\r\n28.03.2013",
					"publication_date": "2013-03-28",
					"modified_date": "2019-06-30",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "1602",
					"title": "New Handbook Launches",
					"description": "The eighth edition of the MLA Handbook, which debuted at the Association of Writers and Writing Programs Conference and Bookfair, has received praise for its more streamlined approach to documentation. Created for a digital era in which publications migrate fluidly among different media, the new MLA style emphasizes the elements common to most works instead [&hellip;]",
					"owner": {
						"name": "Anna Chang",
						"username": "annachang",
						"url": "https://news.mla.commons-wordpress.lndo.site/members/annachang",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Anna Chang",
							"username": "annachang",
							"url": "https://news.mla.commons-wordpress.lndo.site/members/annachang",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://news.mla.commons-wordpress.lndo.site/2016/04/05/new-handbook-launches/",
					"content": "The eighth edition of the MLA Handbook, which debuted at the Association of Writers and Writing Programs Conference and Bookfair, has received praise for its more streamlined approach to documentation. Created for a digital era in which publications migrate fluidly among different media, the new MLA style emphasizes the elements common to most works instead of publication format. Now, writers in all fields—from the sciences to the humanities—have the tools to intuitively document sources. As Kathleen Fitzpatrick, the MLA’s associate executive director and director of scholarly communication, told Inside Higher Ed, the new handbook “focuses on principles—not just on how to create a citation that is correct, but on the purposes of citation practice, as well as on strategies for evaluating sources.” It’s an approach that, according to Michael Greer, a lecturer in rhetoric and writing who is quoted in IHE, “is better aligned with instructors’ focus on process and critical thinking when teaching students the basics of writing with sources.” The change seeks to better support the fundamental goals of citation, as Fitzpatrick writes in the Los Angeles Review of Books: “to enable disparate pieces of scholarly writing to be connected with one another, and to communicate those connections reliably, simply, and clearly.”\r\n\r\n&nbsp;",
					"publication_date": "2016-04-05",
					"modified_date": "2016-04-05",
					"content_type": "post",
					"network_node": "hc"
				},
				{
					"_internal_id": "74",
					"title": "About the Author",
					"description": "Kathleen Fitzpatrick is Director of Digital Humanities and Professor of English at Michigan State University. Prior to assuming this role in 2017, she served as Associate Executive Director and Director of Scholarly Communication of the Modern Language Association, where she was Managing Editor of PMLA and other MLA publications, as well as overseeing the development [&hellip;]",
					"owner": {
						"name": "Kathleen Fitzpatrick",
						"username": "kfitz",
						"url": "https://generousthinking.commons-wordpress.lndo.site/members/kfitz",
						"role": "author",
						"network_node": "hc"
					},
					"contributors": [
						{
							"name": "Kathleen Fitzpatrick",
							"username": "kfitz",
							"url": "https://generousthinking.commons-wordpress.lndo.site/members/kfitz",
							"role": "author",
							"network_node": "hc"
						}
					],
					"primary_url": "https://generousthinking.commons-wordpress.lndo.site/about-the-author/",
					"content": "Kathleen Fitzpatrick is Director of Digital Humanities and Professor of English at Michigan State University. Prior to assuming this role in 2017, she served as Associate Executive Director and Director of Scholarly Communication of the Modern Language Association, where she was Managing Editor of PMLA and other MLA publications, as well as overseeing the development of the MLA Handbook. During that time, she also held an appointment as Visiting Research Professor of English at NYU  and Visiting Professor of Media Studies at Coventry University. Before joining the MLA staff in 2011, she was Professor of Media Studies at Pomona College, where she had been a member of the faculty since 1998.\r\n\r\nFitzpatrick is author of Planned Obsolescence:  Publishing, Technology, and the Future of the Academy (NYU Press, 2011) and of The Anxiety of Obsolescence: The American Novel in the Age of Television (Vanderbilt University Press, 2006). She is project director of Humanities Commons, an open-access, open-source network serving more than 12,500 scholars and practitioners in the humanities. She is also co-founder of the digital scholarly network MediaCommons, where she led a number of experiments in open peer review and other innovations in scholarly publishing. She serves on the editorial or advisory boards of publications and projects including the Open Library of the Humanities, Luminos, and thresholds. She currently serves as the chair of the board of trustees of the Council on Library and Information Resources.",
					"publication_date": "2018-02-07",
					"modified_date": "2019-02-17",
					"content_type": "post",
					"network_node": "hc"
				}
			]
		}';
    }

    public function update_options( WP_REST_Request $request ) : WP_REST_Response {
        $options = $request->get_params();
        $sanitized_options = $this->validate_and_sanitize_options( $options );
        if ( $sanitized_options instanceof WP_REST_Response ) {
            return $sanitized_options;
        }
        update_option( 'cc_client_options', $sanitized_options );
        return new WP_REST_Response( $sanitized_options, 200 );
    }

    protected function validate_and_sanitize_options( array $options ) : array | WP_REST_Response {
        $is_valid_data = rest_validate_value_from_schema( $options, $this->get_item_schema(), 'options' );
        if ( ! $is_valid_data ) {
            return new WP_REST_Response( 'Returned options failed to validate.', 400 );
        }

        $sanitized_options = rest_sanitize_value_from_schema( $options, $this->get_item_schema(), 'options' );
        if ( is_wp_error( $sanitized_options ) ) {
            return new WP_REST_Response( $sanitized_options->get_error_message(), 400 );
        }

        return $sanitized_options;
    }

    public function get_item_schema() : array {
        if ( $this->schema ) {
            return $this->schema;
        }

        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'cc_client_options',
            'type'    => 'object',
            'properties' => [
                'cc_server_url' => [
                    'description' => esc_html__( 'URL of the Commons Connect server.', 'cc-client' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                ],
                'cc_search_endpoint' => [
                    'description' => esc_html__( 'Search endpoint on the Commons Connect server.', 'cc-client' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                ],
                'cc_search_key' => [
                    'description' => esc_html__( 'API key for the Commons Connect search endpoint.', 'cc-client' ),
                    'type'        => 'string',
                ],
            ]
        ];

        return $schema;
    }
}
