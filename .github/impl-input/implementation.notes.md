# Website structure

## General Components
 All these general components should be set on the Theme Options acf page

- Clients
  - The list of clients
  - Backend: ACF Repeater field with the following subfields:
    - Client Name (text)
    - Client Logo (image)
    - Client URL (url)


## Templates
- Home Page
- Studio page
- Advisory page
- Contact page
- Work page:
  - wordrpess archive page with url: /projects/
  - custom post type: project
  - categories of projects: project-category
    - Mix Use Spaces
    - Urban Houses
    - Villas & Hospitality
    - Commercial & F&B
    - Art & Experience Design
    - Advisory Projects
  - Project page:
    - custom post type: project
    - single project page with url: /projects/project-name/
    - custom fields for project:
      - Project Name (text)
      - Project Description (wysiwyg)
      - Project Category (taxonomy: project-category)
      - Project Images (gallery) with the custom grid layout for the images
      - Project Video (video from youtube embed with custom player)
      - Metadata:
        - Client (relationship field to Clients)
        - Project Year (text)
        - Status (select: Completed, In Progress, Upcoming)
        - Project Location (text)
- Journal page:
  - wordrpess default posts as the journal shuld be the bolgpost section
  - Entry Types
    - Project notes — process, material detail, behind the work
    - Field dispatches — from advisory assignments, workshops, site visits
    - Essays & positions — ideas, reflections, spatial thinking
    - References — books, places, precedents that matter to the studio
    - Publications
  - Journal page:
    - custom post type: journal
    - single journal page with url: /journal/journal-entry-name/
    - custom fields for journal entry:
      - Journal Entry Title (text)
      - Journal Entry Content (wysiwyg)
      - Journal Entry Type (taxonomy: journal-entry-type)
      - Journal Entry Images (gallery) with the custom grid layout for the images
      - Journal Entry Video (video from youtube embed with custom player)
      - Metadata:
        - Author (relationship field to Authors)
        - Journal Entry Date (date picker)
        - Status (select: Published, Draft, Archived)